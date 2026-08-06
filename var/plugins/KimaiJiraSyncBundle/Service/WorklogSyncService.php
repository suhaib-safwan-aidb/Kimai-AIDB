<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use Doctrine\ORM\EntityManagerInterface;
use KimaiPlugin\KimaiJiraSyncBundle\Jira\JiraClientException;
use KimaiPlugin\KimaiJiraSyncBundle\Jira\JiraClientFactoryInterface;
use KimaiPlugin\KimaiJiraSyncBundle\Jira\JiraClientInterface;
use KimaiPlugin\KimaiJiraSyncBundle\Jira\JiraWorklogData;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

final class WorklogSyncService implements WorklogSyncServiceInterface
{
    public function __construct(
        private readonly JiraCredentialServiceInterface $credentialService,
        private readonly LoggerInterface $logger,
        private readonly JiraClientFactoryInterface $clientFactory,
        private readonly FreemiumGuardInterface $freemiumGuard,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function syncCreated(Timesheet $timesheet): void
    {
        $context = $this->buildContext($timesheet);
        if ($context === null) {
            return;
        }

        [$client, $issueKey, $timeSpent] = $context;

        try {
            $worklogId = $client->addWorklog(new JiraWorklogData(
                issueKey: $issueKey,
                started: $timesheet->getBegin() ?? new \DateTimeImmutable(),
                timeSpentSeconds: $timeSpent,
                comment: $this->buildComment($timesheet, $issueKey),
            ));

            $this->setMetaFields($timesheet, $worklogId, 'synced');
        } catch (JiraClientException $e) {
            $this->logger->error('KimaiJiraSync: failed to add worklog', [
                'timesheet_id' => $timesheet->getId(),
                'issue_key'    => $issueKey,
                'error'        => $e->getMessage(),
            ]);
            $this->setMetaFields($timesheet, null, 'error');
        }
    }

    public function syncUpdated(Timesheet $timesheet, ?string $previousIssueKey = null): void
    {
        $context = $this->buildContext($timesheet);
        $existingWorklogId = $this->getMetaValue($timesheet, 'jira_worklog_id');

        // Issue key changed → delete old worklog and create new one
        if ($previousIssueKey !== null && $context !== null) {
            [$client, $newIssueKey] = $context;
            if ($previousIssueKey !== $newIssueKey && $existingWorklogId !== null) {
                $this->deleteWorklogSilently($client, $previousIssueKey, $existingWorklogId, $timesheet);
                $this->syncCreated($timesheet);
                return;
            }
        }

        if ($context === null) {
            return;
        }

        [$client, $issueKey, $timeSpent] = $context;

        if ($existingWorklogId !== null) {
            try {
                $client->updateWorklog($issueKey, $existingWorklogId, new JiraWorklogData(
                    issueKey: $issueKey,
                    started: $timesheet->getBegin() ?? new \DateTimeImmutable(),
                    timeSpentSeconds: $timeSpent,
                    comment: $this->buildComment($timesheet, $issueKey),
                ));
                $this->setMetaFields($timesheet, $existingWorklogId, 'synced');
            } catch (JiraClientException $e) {
                $this->logger->error('KimaiJiraSync: failed to update worklog', [
                    'timesheet_id' => $timesheet->getId(),
                    'worklog_id'   => $existingWorklogId,
                    'issue_key'    => $issueKey,
                    'error'        => $e->getMessage(),
                ]);
                $this->setMetaFields($timesheet, $existingWorklogId, 'error');
            }
        } else {
            $this->syncCreated($timesheet);
        }
    }

    public function syncDeleted(Timesheet $timesheet): void
    {
        $context = $this->buildContext($timesheet);
        $worklogId = $this->getMetaValue($timesheet, 'jira_worklog_id');

        if ($context === null || $worklogId === null) {
            return;
        }

        [$client, $issueKey] = $context;
        $this->deleteWorklogSilently($client, $issueKey, $worklogId, $timesheet);
    }

    /**
     * Returns [JiraClientInterface, issueKey, timeSpentSeconds] or null if sync should be skipped.
     *
     * @return array{0: JiraClientInterface, 1: string, 2: int}|null
     */
    private function buildContext(Timesheet $timesheet): ?array
    {
        // Only sync completed timesheets
        if ($timesheet->getEnd() === null) {
            return null;
        }

        $project = $timesheet->getProject();
        if ($project === null) {
            return null;
        }

        $instanceUrl = $project->getMetaField('jira_instance_url')?->getValue();
        if (empty($instanceUrl)) {
            return null;
        }

        $projectId = $project->getId();
        if ($projectId === null || !$this->freemiumGuard->isProjectAllowed($projectId)) {
            return null;
        }

        $issueKey = $this->resolveIssueKey($timesheet);
        if ($issueKey === null) {
            return null;
        }

        $user = $timesheet->getUser();
        if ($user === null) {
            return null;
        }

        $credential = $this->credentialService->findByUserAndProject(
            $user->getId(),
            $projectId,
        );

        if ($credential === null) {
            return null;
        }

        $plainToken = $this->credentialService->getDecryptedToken($credential);
        $client = $this->clientFactory->create($instanceUrl, $credential->getJiraUsername(), $plainToken);

        $multiplier = (float) ($project->getMetaField('time_multiplier')?->getValue() ?? '1.0');
        $duration = (int) $timesheet->getDuration();
        $timeSpent = (int) round($duration * $multiplier);

        if ($timeSpent <= 0) {
            return null;
        }

        return [$client, $issueKey, $timeSpent];
    }

    private function resolveIssueKey(Timesheet $timesheet): ?string
    {
        // Priority 1: activity meta field jira_issue_key
        $activity = $timesheet->getActivity();
        if ($activity !== null) {
            $key = $activity->getMetaField('jira_issue_key')?->getValue();
            if (!empty($key)) {
                return $key;
            }
        }

        // Priority 2: prefix in description "PROJECT-123: description"
        $description = $timesheet->getDescription() ?? '';
        if (preg_match('/^([A-Z][A-Z0-9_]+-\d+)\s*:/u', $description, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Builds the comment to send to Jira.
     * Strips the issue key prefix from description if present.
     */
    private function buildComment(Timesheet $timesheet, string $issueKey): string
    {
        $description = $timesheet->getDescription() ?? '';
        // Remove "PROJECT-123: " prefix from comment
        $comment = preg_replace('/^' . preg_quote($issueKey, '/') . '\s*:\s*/u', '', $description);
        return trim($comment ?? $description);
    }

    private function setMetaFields(Timesheet $timesheet, ?string $worklogId, string $status): void
    {
        $this->persistMetaValue($timesheet, 'jira_worklog_id', $worklogId);
        $this->persistMetaValue($timesheet, 'sync_status', $status);
        if ($status === 'synced') {
            $this->persistMetaValue($timesheet, 'synced_at', (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM));
        }

        $this->entityManager->flush();
    }

    private function persistMetaValue(Timesheet $timesheet, string $fieldName, ?string $value): void
    {
        $meta = $timesheet->getMetaField($fieldName);

        if ($meta === null) {
            $meta = new TimesheetMeta();
            $meta->setName($fieldName)
                 ->setType(HiddenType::class)
                 ->setIsVisible(false)
                 ->setIsRequired(false);
            $timesheet->setMetaField($meta);
        }

        $meta->setValue($value);
        $this->entityManager->persist($meta);
    }

    private function getMetaValue(Timesheet $timesheet, string $fieldName): ?string
    {
        $value = $timesheet->getMetaField($fieldName)?->getValue();
        return (is_string($value) && $value !== '') ? $value : null;
    }

    private function deleteWorklogSilently(
        JiraClientInterface $client,
        string $issueKey,
        string $worklogId,
        Timesheet $timesheet,
    ): void {
        try {
            $client->deleteWorklog($issueKey, $worklogId);
        } catch (JiraClientException $e) {
            $this->logger->error('KimaiJiraSync: failed to delete worklog', [
                'timesheet_id' => $timesheet->getId(),
                'worklog_id'   => $worklogId,
                'issue_key'    => $issueKey,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}
