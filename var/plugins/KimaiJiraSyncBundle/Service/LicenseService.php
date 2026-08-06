<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

use KimaiPlugin\KimaiJiraSyncBundle\Entity\LicenseActivation;
use KimaiPlugin\KimaiJiraSyncBundle\Repository\JiraCredentialRepositoryInterface;
use KimaiPlugin\KimaiJiraSyncBundle\Repository\LicenseActivationRepositoryInterface;

final class LicenseService implements FreemiumGuardInterface
{
    public function __construct(
        private readonly LicenseClientInterface $licenseClient,
        private readonly LicenseActivationRepositoryInterface $repository,
        private readonly JiraCredentialRepositoryInterface $credentialRepository,
        private readonly string $instanceId = '',
    ) {
    }

    private function resolveInstanceName(): string
    {
        if ($this->instanceId !== '') {
            return $this->instanceId;
        }

        return (string) (gethostname() ?: 'unknown');
    }

    /**
     * Activate a license key against the remote license server and persist the result.
     *
     * @throws LicenseException when the remote call fails or the license is not valid
     */
    public function activate(string $licenseKey): LicenseActivation
    {
        $instanceName = $this->resolveInstanceName();
        $response = $this->licenseClient->activate($licenseKey, $instanceName);

        if (!$response->valid) {
            throw new LicenseException('License key is not valid or could not be activated.');
        }

        $this->repository->deleteAll();

        $instanceId = $response->instanceId ?? $instanceName;

        $activation = new LicenseActivation(
            licenseKey: $licenseKey,
            instanceId: $instanceId,
            status: $response->status,
            expiresAt: $response->expiresAt,
        );
        $activation->setLastVerifiedAt(new \DateTimeImmutable());

        $this->repository->save($activation);

        return $activation;
    }

    /**
     * Verify the currently stored license against the remote license server.
     *
     * @throws LicenseException when no license is stored, or the remote call fails
     */
    public function verify(): LicenseActivation
    {
        $activation = $this->repository->findLatest();

        if ($activation === null) {
            throw new LicenseException('No license found. Please activate a license first.');
        }

        try {
            $response = $this->licenseClient->verify($activation->getLicenseKey(), $activation->getInstanceId());
        } catch (LicenseInvalidInstanceException $e) {
            $this->repository->deleteAll();
            throw $e;
        }

        $activation->setStatus($response->status);
        $activation->setExpiresAt($response->expiresAt);
        $activation->setLastVerifiedAt(new \DateTimeImmutable());

        $this->repository->save($activation);

        return $activation;
    }

    public function getCurrent(): ?LicenseActivation
    {
        return $this->repository->findLatest();
    }

    /**
     * Deactivate the currently stored license on the remote license server and remove local data.
     *
     * @throws LicenseException when no license is stored, or the remote call fails
     */
    public function deactivate(): void
    {
        $activation = $this->repository->findLatest();

        if ($activation === null) {
            throw new LicenseException('No license found. Please activate a license first.');
        }

        $this->licenseClient->deactivate($activation->getLicenseKey(), $activation->getInstanceId());

        $this->repository->deleteAll();
    }

    public function isLicenseActive(): bool
    {
        // The free-tier project limit has been removed; license activation is kept
        // for support/tracking purposes only and no longer gates functionality.
        return true;
    }

    /**
     * Returns the project ID that was included in the (now retired) free tier,
     * or null if no project has been configured yet.
     */
    public function getFreeProjectId(): ?int
    {
        return $this->credentialRepository->findLowestProjectId();
    }

    /**
     * Returns true if the given project is allowed to use Jira sync.
     *
     * All projects are allowed regardless of license status.
     */
    public function isProjectAllowed(int $projectId): bool
    {
        return true;
    }
}
