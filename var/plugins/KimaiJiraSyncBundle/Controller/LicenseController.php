<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Controller;

use App\Controller\AbstractController;
use KimaiPlugin\KimaiJiraSyncBundle\Form\LicenseActivationType;
use KimaiPlugin\KimaiJiraSyncBundle\Service\LicenseException;
use KimaiPlugin\KimaiJiraSyncBundle\Service\LicenseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/jira-sync/license')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class LicenseController extends AbstractController
{
    public function __construct(
        private readonly LicenseService $licenseService,
    ) {
    }

    #[Route(path: '', name: 'jira_sync_license', methods: ['GET'])]
    public function indexAction(): Response
    {
        return $this->render('@KimaiJiraSync/license/index.html.twig', [
            'license'       => $this->licenseService->getCurrent(),
            'freeProjectId' => $this->licenseService->getFreeProjectId(),
            'form'          => $this->createForm(LicenseActivationType::class)->createView(),
        ]);
    }

    #[Route(path: '/activate', name: 'jira_sync_license_activate', methods: ['POST'])]
    public function activateAction(Request $request): Response
    {
        $form = $this->createForm(LicenseActivationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $licenseKey = (string) $form->get('licenseKey')->getData();

            try {
                $this->licenseService->activate($licenseKey);
                $this->addFlash('success', 'jira_sync.license.activated');
            } catch (LicenseException $e) {
                $this->addFlash('error', 'jira_sync.license.activation_failed');
            }

            return $this->redirectToRoute('jira_sync_license');
        }

        return $this->render('@KimaiJiraSync/license/index.html.twig', [
            'license'       => $this->licenseService->getCurrent(),
            'freeProjectId' => $this->licenseService->getFreeProjectId(),
            'form'          => $form->createView(),
        ]);
    }

    #[Route(path: '/verify', name: 'jira_sync_license_verify', methods: ['POST'])]
    public function verifyAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('verify-license', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'jira_sync.sync.invalid_token');
            return $this->redirectToRoute('jira_sync_license');
        }

        try {
            $this->licenseService->verify();
            $this->addFlash('success', 'jira_sync.license.verified');
        } catch (LicenseException $e) {
            $this->addFlash('error', 'jira_sync.license.verification_failed');
        }

        return $this->redirectToRoute('jira_sync_license');
    }

    #[Route(path: '/deactivate', name: 'jira_sync_license_deactivate', methods: ['POST'])]
    public function deactivateAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('deactivate-license', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'jira_sync.sync.invalid_token');
            return $this->redirectToRoute('jira_sync_license');
        }

        try {
            $this->licenseService->deactivate();
            $this->addFlash('success', 'jira_sync.license.deactivated');
        } catch (LicenseException $e) {
            $this->addFlash('error', 'jira_sync.license.deactivation_failed');
        }

        return $this->redirectToRoute('jira_sync_license');
    }
}
