<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Controller;

use App\Controller\AbstractController;
use KimaiPlugin\KimaiJiraSyncBundle\Form\JiraCredentialType;
use KimaiPlugin\KimaiJiraSyncBundle\Service\JiraCredentialService;
use KimaiPlugin\KimaiJiraSyncBundle\Service\TaskImportService;
use App\Entity\Project;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/jira-sync')]
#[IsGranted('ROLE_ADMIN')]
final class CredentialController extends AbstractController
{
    public function __construct(
        private readonly JiraCredentialService $credentialService,
        private readonly ManagerRegistry $doctrine,
        private readonly TaskImportService $taskImportService,
    ) {
    }

    #[Route(path: '/credentials', name: 'jira_sync_credentials', methods: ['GET'])]
    public function indexAction(): Response
    {
        return $this->render('@KimaiJiraSync/credential/index.html.twig', [
            'credentials' => $this->credentialService->findAll(),
        ]);
    }

    #[Route(path: '/credentials/new', name: 'jira_sync_credentials_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request): Response
    {
        $form = $this->createForm(JiraCredentialType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userId = (int) $data['userId']->getId();
            $projectId = (int) $data['projectId']->getId();

            $this->credentialService->save(
                $userId,
                $projectId,
                (string) $data['jiraUsername'],
                (string) $data['jiraApiToken'],
            );

            $this->addFlash('success', 'jira_sync.credentials.saved');
            return $this->redirectToRoute('jira_sync_credentials');
        }

        return $this->render('@KimaiJiraSync/credential/form.html.twig', [
            'form'  => $form->createView(),
            'title' => 'jira_sync.credentials.add',
        ]);
    }

    #[Route(path: '/credentials/{id}/edit', name: 'jira_sync_credentials_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, int $id): Response
    {
        $credential = $this->credentialService->findById($id);

        if ($credential === null) {
            throw $this->createNotFoundException('Credential not found');
        }

        $form = $this->createForm(JiraCredentialType::class, [
            'jiraUsername' => $credential->getJiraUsername(),
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
                $credential->setJiraUsername((string) $data['jiraUsername']);
                $this->credentialService->save(
                    $credential->getUserId(),
                    $credential->getProjectId(),
                    (string) $data['jiraUsername'],
                    $this->credentialService->getDecryptedToken($credential),
                );
            }

            $this->addFlash('success', 'jira_sync.credentials.saved');
            return $this->redirectToRoute('jira_sync_credentials');
        }

        return $this->render('@KimaiJiraSync/credential/form.html.twig', [
            'form'  => $form->createView(),
            'title' => 'jira_sync.credentials.edit',
        ]);
    }

    #[Route(path: '/credentials/{id}/sync', name: 'jira_sync_credentials_sync', methods: ['POST'])]
    public function syncAction(Request $request, int $id): Response
    {
        $credential = $this->credentialService->findById($id);

        if ($credential === null) {
            throw $this->createNotFoundException('Credential not found');
        }

        if (!$this->isCsrfTokenValid('sync-credential-' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'jira_sync.sync.invalid_token');
            return $this->redirectToRoute('jira_sync_credentials');
        }

        $project = $this->doctrine->getRepository(Project::class)->find($credential->getProjectId());
        if ($project === null) {
            throw $this->createNotFoundException('Project not found');
        }

        $this->taskImportService->importForProject($project, $credential->getUserId());
        $this->addFlash('success', 'jira_sync.sync.success');

        return $this->redirectToRoute('jira_sync_credentials');
    }

    #[Route(path: '/credentials/{id}/delete', name: 'jira_sync_credentials_delete', methods: ['POST'])]
    public function deleteAction(Request $request, int $id): Response
    {
        $credential = $this->credentialService->findById($id);

        if ($credential === null) {
            throw $this->createNotFoundException('Credential not found');
        }

        if ($this->isCsrfTokenValid('delete-credential-' . $id, (string) $request->request->get('_token'))) {
            $this->credentialService->delete($credential);
            $this->addFlash('success', 'jira_sync.credentials.deleted');
        }

        return $this->redirectToRoute('jira_sync_credentials');
    }
}
