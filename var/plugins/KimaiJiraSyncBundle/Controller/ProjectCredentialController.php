<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Project;
use App\Repository\UserRepository;
use KimaiPlugin\KimaiJiraSyncBundle\Entity\JiraCredential;
use KimaiPlugin\KimaiJiraSyncBundle\Form\ProjectCredentialType;
use KimaiPlugin\KimaiJiraSyncBundle\Service\FreemiumGuardInterface;
use KimaiPlugin\KimaiJiraSyncBundle\Service\JiraCredentialServiceInterface;
use KimaiPlugin\KimaiJiraSyncBundle\Service\TaskImportService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/jira-sync/project')]
final class ProjectCredentialController extends AbstractController
{
    public function __construct(
        private readonly JiraCredentialServiceInterface $credentialService,
        private readonly ManagerRegistry $doctrine,
        private readonly TaskImportService $taskImportService,
        private readonly UserRepository $userRepository,
        private readonly FreemiumGuardInterface $freemiumGuard,
    ) {
    }

    /**
     * Embedded panel rendered on the project detail page via ProjectDetailControllerEvent.
     */
    public function panelAction(Project $project): Response
    {
        $projectId = $project->getId();
        if ($projectId === null) {
            return new Response('');
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $currentUser = $this->getUser();

        if ($isAdmin) {
            $credentials = $this->credentialService->findByProject($projectId);
        } else {
            $credential = $this->credentialService->findByUserAndProject(
                $currentUser->getId(),
                $projectId
            );
            $credentials = $credential !== null ? [$credential] : [];
        }

        $usernames = [];
        if ($isAdmin && $credentials !== []) {
            $userIds = array_unique(array_map(
                static fn (JiraCredential $c) => $c->getUserId(),
                $credentials,
            ));
            $users = $this->userRepository->findByIds($userIds);
            foreach ($users as $user) {
                $usernames[$user->getId()] = $user->getUserIdentifier();
            }
        }

        return $this->render('@KimaiJiraSync/project/credentials_panel.html.twig', [
            'project'            => $project,
            'credentials'        => $credentials,
            'is_admin'           => $isAdmin,
            'usernames'          => $usernames,
            'is_project_allowed' => $this->freemiumGuard->isProjectAllowed($projectId),
        ]);
    }

    #[Route(path: '/{projectId}/credentials/new', name: 'jira_sync_project_credential_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request, int $projectId): Response
    {
        $project = $this->loadProject($projectId);

        if (!$this->freemiumGuard->isProjectAllowed($projectId)) {
            $this->addFlash('error', 'jira_sync.freemium.project_not_allowed');
            return $this->redirectToRoute('project_details', ['id' => $projectId]);
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $form = $this->createForm(ProjectCredentialType::class, [], [
            'is_admin' => $isAdmin,
            'is_edit'  => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userId = $isAdmin
                ? (int) $data['userId']->getId()
                : $this->getUser()->getId();

            $this->credentialService->save(
                $userId,
                $project->getId(),
                (string) $data['jiraUsername'],
                (string) $data['jiraApiToken'],
            );

            $this->addFlash('success', 'jira_sync.credentials.saved');

            return $this->redirectToRoute('project_details', ['id' => $projectId]);
        }

        return $this->render('@KimaiJiraSync/project/credential_form.html.twig', [
            'form'    => $form->createView(),
            'title'   => 'jira_sync.credentials.add',
            'project' => $project,
        ]);
    }

    #[Route(path: '/{projectId}/credentials/{id}/edit', name: 'jira_sync_project_credential_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, int $projectId, int $id): Response
    {
        $project = $this->loadProject($projectId);
        $credential = $this->loadCredential($id);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$this->canManage($credential)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProjectCredentialType::class, [
            'jiraUsername' => $credential->getJiraUsername(),
        ], [
            'is_admin' => false,
            'is_edit'  => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $plainToken = (string) $data['jiraApiToken'];

            if ($plainToken !== '') {
                $this->credentialService->save(
                    $credential->getUserId(),
                    $credential->getProjectId(),
                    (string) $data['jiraUsername'],
                    $plainToken,
                );
            } else {
                $this->credentialService->save(
                    $credential->getUserId(),
                    $credential->getProjectId(),
                    (string) $data['jiraUsername'],
                    $this->credentialService->getDecryptedToken($credential),
                );
            }

            $this->addFlash('success', 'jira_sync.credentials.saved');

            return $this->redirectToRoute('project_details', ['id' => $projectId]);
        }

        return $this->render('@KimaiJiraSync/project/credential_form.html.twig', [
            'form'    => $form->createView(),
            'title'   => 'jira_sync.credentials.edit',
            'project' => $project,
        ]);
    }

    #[Route(path: '/{projectId}/credentials/{id}/delete', name: 'jira_sync_project_credential_delete', methods: ['POST'])]
    public function deleteAction(Request $request, int $projectId, int $id): Response
    {
        $credential = $this->loadCredential($id);

        if (!$this->canManage($credential)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete-project-credential-' . $id, (string) $request->request->get('_token'))) {
            $this->credentialService->delete($credential);
            $this->addFlash('success', 'jira_sync.credentials.deleted');
        }

        return $this->redirectToRoute('project_details', ['id' => $projectId]);
    }

    #[Route(path: '/{projectId}/sync', name: 'jira_sync_project_sync', methods: ['POST'])]
    public function syncAction(Request $request, int $projectId): Response
    {
        $project = $this->loadProject($projectId);

        if (!$this->isCsrfTokenValid('sync-project-' . $projectId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'jira_sync.sync.invalid_token');
            return $this->redirectToRoute('project_details', ['id' => $projectId]);
        }

        $userId = $this->getUser()->getId();
        $this->taskImportService->importForProject($project, $userId);

        $this->addFlash('success', 'jira_sync.sync.success');

        return $this->redirectToRoute('project_details', ['id' => $projectId]);
    }

    private function loadProject(int $id): Project
    {
        $project = $this->doctrine->getRepository(Project::class)->find($id);
        if ($project === null) {
            throw $this->createNotFoundException('Project not found');
        }

        return $project;
    }

    private function loadCredential(int $id): JiraCredential
    {
        $credential = $this->credentialService->findById($id);
        if ($credential === null) {
            throw $this->createNotFoundException('Credential not found');
        }

        return $credential;
    }

    private function canManage(JiraCredential $credential): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return $credential->getUserId() === $this->getUser()->getId();
    }
}
