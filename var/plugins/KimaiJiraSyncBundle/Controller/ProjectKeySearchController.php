<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Project;
use KimaiPlugin\KimaiJiraSyncBundle\Jira\JiraClientException;
use KimaiPlugin\KimaiJiraSyncBundle\Jira\JiraClientFactoryInterface;
use KimaiPlugin\KimaiJiraSyncBundle\Service\JiraCredentialServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Backing endpoint for the "jira_project_key" project meta field autocomplete
 * (see Form\JiraProjectKeySearchType and assets/js/forms/KimaiAutocomplete.js in Kimai core).
 *
 * Searches Jira projects on the instance configured for the given Kimai project,
 * using the requesting user's own saved Jira credentials for that project.
 */
#[Route(path: '/jira-sync/project/{id}/jira-projects/search', name: 'jira_sync_project_key_search', methods: ['GET'])]
#[IsGranted('edit', 'project')]
final class ProjectKeySearchController extends AbstractController
{
    public function __construct(
        private readonly JiraCredentialServiceInterface $credentialService,
        private readonly JiraClientFactoryInterface $clientFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request, Project $project): JsonResponse
    {
        $query = trim((string) $request->query->get('name', ''));
        if ($query === '' || $project->getId() === null) {
            return new JsonResponse([]);
        }

        $instanceUrl = $project->getMetaField('jira_instance_url')?->getValue();
        if (!\is_string($instanceUrl) || $instanceUrl === '') {
            return new JsonResponse([]);
        }

        $credential = $this->credentialService->findByUserAndProject($this->getUser()->getId(), $project->getId());
        if ($credential === null) {
            return new JsonResponse([]);
        }

        try {
            $client = $this->clientFactory->create(
                $instanceUrl,
                $credential->getJiraUsername(),
                $this->credentialService->getDecryptedToken($credential),
            );

            $results = $client->searchProjects($query);
        } catch (JiraClientException $e) {
            $this->logger->error('KimaiJiraSync: project key search failed', [
                'project_id' => $project->getId(),
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([]);
        }

        // KimaiAutocomplete.js uses "name" both as the shown suggestion and the value written
        // back into the field, so this must be the plain Jira key, not a decorated label.
        $data = array_map(
            static fn (array $p) => ['name' => $p['key']],
            $results,
        );

        return new JsonResponse($data);
    }
}
