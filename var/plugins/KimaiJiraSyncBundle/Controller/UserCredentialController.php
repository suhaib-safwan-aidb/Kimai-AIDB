<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Project;
use Doctrine\Persistence\ManagerRegistry;
use KimaiPlugin\KimaiJiraSyncBundle\Entity\JiraCredential;
use KimaiPlugin\KimaiJiraSyncBundle\Form\UserCredentialType;
use KimaiPlugin\KimaiJiraSyncBundle\Service\FreemiumGuardInterface;
use KimaiPlugin\KimaiJiraSyncBundle\Service\JiraCredentialServiceInterface;
use KimaiPlugin\KimaiJiraSyncBundle\Service\TaskImportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/jira-sync/my-credentials')]
final class UserCredentialController extends AbstractController
{
    public function __construct(
        private readonly JiraCredentialServiceInterface $credentialService,
        private readonly ManagerRegistry $doctrine,
        private readonly TaskImportService $taskImportService,
        private readonly FreemiumGuardInterface $freemiumGuard,
    ) {
    }

    #[Route(path: '', name: 'jira_sync_my_credentials', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function indexAction(): Response
    {
        $user = $this->getUser();
        $credentials = $this->credentialService->findByUser($user->getId());

        $projectNames = [];
        $projectIds = array_unique(array_map(
            static fn (JiraCredential $c) => $c->getProjectId(),
            $credentials,
        ));

        $syncTasksEnabled = [];

        if ($projectIds !== []) {
            $projects = $this->doctrine->getRepository(Project::class)->findBy(['id' => $projectIds]);
            foreach ($projects as $project) {
                $projectNames[$project->getId()] = $project->getName();
                $syncTasksEnabled[$project->getId()] = $project->getMetaField('sync_tasks_enabled')?->getValue() === '1';
            }
        }

        // Hide credentials for deleted projects (project no longer exists in DB)
        $existingProjectIds = array_keys($projectNames);
        $credentials = array_values(array_filter(
            $credentials,
            static fn (JiraCredential $c) => \in_array($c->getProjectId(), $existingProjectIds, true),
        ));

        return $this->render('@KimaiJiraSync/user_credential/index.html.twig', [
            'credentials'      => $credentials,
            'projectNames'     => $projectNames,
            'syncTasksEnabled' => $syncTasksEnabled,
            'can_add_more'     => $this->freemiumGuard->isLicenseActive() || $credentials === [],
        ]);
    }

    #[Route(path: '/new', name: 'jira_sync_my_credentials_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function newAction(Request $request): Response
    {
        $user = $this->getUser();
        if (!$this->freemiumGuard->isLicenseActive() && $this->credentialService->findByUser($user->getId()) !== []) {
            $this->addFlash('error', 'jira_sync.freemium.no_more_credentials');
            return $this->redirectToRoute('jira_sync_my_credentials');
        }

        $form = $this->createForm(UserCredentialType::class, [], ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $projectId = (int) $data['projectId']->getId();

            $this->credentialService->save(
                $this->getUser()->getId(),
                $projectId,
                (string) $data['jiraUsername'],
                (string) $data['jiraApiToken'],
            );

            $this->addFlash('success', 'jira_sync.credentials.saved');

            return $this->redirectToRoute('jira_sync_my_credentials');
        }

        return $this->render('@KimaiJiraSync/user_credential/form.html.twig', [
            'form'  => $form->createView(),
            'title' => 'jira_sync.credentials.add',
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'jira_sync_my_credentials_edit', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function editAction(Request $request, int $id): Response
    {
        $credential = $this->loadOwnCredential($id);

        $form = $this->createForm(UserCredentialType::class, [
            'projectId'    => null,
            'jiraUsername' => $credential->getJiraUsername(),
        ], ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $plainToken = (string) $data['jiraApiToken'];

            $this->credentialService->save(
                $credential->getUserId(),
                $credential->getProjectId(),
                (string) $data['jiraUsername'],
                $plainToken !== '' ? $plainToken : $this->credentialService->getDecryptedToken($credential),
            );

            $this->addFlash('success', 'jira_sync.credentials.saved');

            return $this->redirectToRoute('jira_sync_my_credentials');
        }

        return $this->render('@KimaiJiraSync/user_credential/form.html.twig', [
            'form'  => $form->createView(),
            'title' => 'jira_sync.credentials.edit',
        ]);
    }

    #[Route(path: '/all/sync', name: 'jira_sync_my_credentials_sync_all', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function syncAllAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('sync-all-my-credentials', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'jira_sync.sync.invalid_token');
            return $this->redirectToRoute('jira_sync_my_credentials');
        }

        $user = $this->getUser();
        $credentials = $this->credentialService->findByUser($user->getId());

        $syncCount = 0;
        foreach ($credentials as $credential) {
            $project = $this->doctrine->getRepository(Project::class)->find($credential->getProjectId());
            if ($project !== null) {
                $this->taskImportService->importForProject($project, $user->getId());
                $syncCount++;
            }
        }

        if ($syncCount > 0) {
            $this->addFlash('success', 'jira_sync.sync.all_success');
        } else {
            $this->addFlash('warning', 'jira_sync.sync.no_projects');
        }

        return $this->redirectToRoute('jira_sync_my_credentials');
    }

    #[Route(path: '/{id}/sync', name: 'jira_sync_my_credentials_sync', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function syncAction(Request $request, int $id): Response
    {
        $credential = $this->loadOwnCredential($id);

        if (!$this->isCsrfTokenValid('sync-my-credential-' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'jira_sync.sync.invalid_token');
            return $this->redirectToRoute('jira_sync_my_credentials');
        }

        $project = $this->doctrine->getRepository(Project::class)->find($credential->getProjectId());
        if ($project === null) {
            throw $this->createNotFoundException('Project not found');
        }

        $this->taskImportService->importForProject($project, $this->getUser()->getId());
        $this->addFlash('success', 'jira_sync.sync.success');

        return $this->redirectToRoute('jira_sync_my_credentials');
    }

    #[Route(path: '/{id}/delete', name: 'jira_sync_my_credentials_delete', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteAction(Request $request, int $id): Response
    {
        $credential = $this->loadOwnCredential($id);

        if ($this->isCsrfTokenValid('delete-my-credential-' . $id, (string) $request->request->get('_token'))) {
            $this->credentialService->delete($credential);
            $this->addFlash('success', 'jira_sync.credentials.deleted');
        }

        return $this->redirectToRoute('jira_sync_my_credentials');
    }

    private function loadOwnCredential(int $id): JiraCredential
    {
        $credential = $this->credentialService->findById($id);

        if ($credential === null || $credential->getUserId() !== $this->getUser()->getId()) {
            throw $this->createNotFoundException('Credential not found');
        }

        return $credential;
    }
}
