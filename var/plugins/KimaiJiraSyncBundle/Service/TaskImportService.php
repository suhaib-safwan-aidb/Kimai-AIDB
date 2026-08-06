<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use KimaiPlugin\KimaiJiraSyncBundle\Jira\JiraClientException;
use KimaiPlugin\KimaiJiraSyncBundle\Jira\JiraClientFactoryInterface;
use Psr\Log\LoggerInterface;

final class TaskImportService
{
    private const ACTIVITY_NAME_MAX_LENGTH = 150;

    public function __construct(
        private readonly JiraCredentialServiceInterface $credentialService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly JiraClientFactoryInterface $clientFactory,
        private readonly FreemiumGuardInterface $freemiumGuard,
    ) {
    }

    /**
     * Imports Jira tasks as Kimai activities for the given project.
     * Returns the number of imported/updated activities.
     */
    public function importForProject(Project $project, int $adminUserId): int
    {
        $instanceUrl = $project->getMetaField('jira_instance_url')?->getValue();
        $jiraProjectKey = strtoupper(trim((string) $project->getMetaField('jira_project_key')?->getValue()));
        $syncEnabled = filter_var(
            $project->getMetaField('sync_tasks_enabled')?->getValue(),
            FILTER_VALIDATE_BOOLEAN,
        );

        if (empty($instanceUrl) || $jiraProjectKey === '' || !$syncEnabled) {
            return 0;
        }

        $projectId = $project->getId();
        if ($projectId === null || !$this->freemiumGuard->isProjectAllowed($projectId)) {
            return 0;
        }

        $credential = $this->credentialService->findByUserAndProject($adminUserId, $project->getId());
        if ($credential === null) {
            $this->logger->warning('KimaiJiraSync: no credentials for task import', [
                'project_id' => $project->getId(),
                'user_id'    => $adminUserId,
            ]);
            return 0;
        }

        $plainToken = $this->credentialService->getDecryptedToken($credential);
        $client = $this->clientFactory->create($instanceUrl, $credential->getJiraUsername(), $plainToken);
        $jql = sprintf('project = "%s" ORDER BY updated DESC', $this->escapeJqlString($jiraProjectKey));

        try {
            $issues = $client->searchIssues($jql);
        } catch (JiraClientException $e) {
            $this->logger->error('KimaiJiraSync: task import JQL failed', [
                'project_id' => $project->getId(),
                'jira_project_key' => $jiraProjectKey,
                'jql'        => $jql,
                'error'      => $e->getMessage(),
            ]);
            return 0;
        }

        $count = 0;
        foreach ($issues as $issue) {
            $issueKey = (string) ($issue['key'] ?? '');
            $summary = (string) ($issue['fields']['summary'] ?? $issueKey);

            if ($issueKey === '') {
                continue;
            }

            $this->upsertActivity($project, $issueKey, $summary);
            $count++;
        }

        $this->entityManager->flush();
        return $count;
    }

    private function upsertActivity(Project $project, string $issueKey, string $summary): void
    {
        $summary = $this->normalizeActivityName($summary);

        $existing = $this->findActivityByIssueKey($project, $issueKey);

        if ($existing !== null) {
            $existing->setName($summary);
            $existing->setComment($issueKey);
            $this->setIssueKeyMeta($existing, $issueKey);
            return;
        }

        $activity = new Activity();
        $activity->setName($summary);
        $activity->setComment($issueKey);
        $activity->setProject($project);
        $activity->setVisible(true);
        $this->setIssueKeyMeta($activity, $issueKey);

        $this->entityManager->persist($activity);
    }

    private function findActivityByIssueKey(Project $project, string $issueKey): ?Activity
    {
        return $this->entityManager->createQuery(
            'SELECT a FROM App\Entity\Activity a
             JOIN a.meta m
             WHERE a.project = :project
               AND m.name = :metaName
               AND m.value = :metaValue'
        )
            ->setParameter('project', $project)
            ->setParameter('metaName', 'jira_issue_key')
            ->setParameter('metaValue', $issueKey)
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    private function setIssueKeyMeta(Activity $activity, string $issueKey): void
    {
        $meta = $activity->getMetaField('jira_issue_key');
        if ($meta !== null) {
            $meta->setValue($issueKey);
            return;
        }

        $meta = new ActivityMeta();
        $meta->setName('jira_issue_key');
        $meta->setValue($issueKey);
        $activity->setMetaField($meta);
    }

    private function normalizeActivityName(string $summary): string
    {
        if (\strlen($summary) <= self::ACTIVITY_NAME_MAX_LENGTH) {
            return $summary;
        }

        if (\function_exists('mb_substr')) {
            return (string) \mb_substr($summary, 0, self::ACTIVITY_NAME_MAX_LENGTH);
        }

        return \substr($summary, 0, self::ACTIVITY_NAME_MAX_LENGTH);
    }

    private function escapeJqlString(string $value): string
    {
        return str_replace('"', '\\"', $value);
    }
}
