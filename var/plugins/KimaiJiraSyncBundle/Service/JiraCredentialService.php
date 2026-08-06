<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

use KimaiPlugin\KimaiJiraSyncBundle\Entity\JiraCredential;
use KimaiPlugin\KimaiJiraSyncBundle\Repository\JiraCredentialRepositoryInterface;

final class JiraCredentialService implements JiraCredentialServiceInterface
{
    public function __construct(
        private readonly JiraCredentialRepositoryInterface $repository,
        private readonly EncryptionService $encryption,
    ) {
    }

    public function findByUserAndProject(int $userId, int $projectId): ?JiraCredential
    {
        return $this->repository->findByUserAndProject($userId, $projectId);
    }

    /** @return JiraCredential[] */
    public function findByProject(int $projectId): array
    {
        return $this->repository->findByProject($projectId);
    }

    /** @return JiraCredential[] */
    public function findByUser(int $userId): array
    {
        return $this->repository->findByUser($userId);
    }

    /** @return JiraCredential[] */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function save(int $userId, int $projectId, string $jiraUsername, string $plainApiToken): JiraCredential
    {
        $existing = $this->repository->findByUserAndProject($userId, $projectId);
        $encryptedToken = $this->encryption->encrypt($plainApiToken);

        if ($existing !== null) {
            $existing->setJiraUsername($jiraUsername);
            $existing->setJiraApiToken($encryptedToken);
            $this->repository->save($existing);
            return $existing;
        }

        $credential = new JiraCredential($userId, $projectId, $jiraUsername, $encryptedToken);
        $this->repository->save($credential);
        return $credential;
    }

    public function getDecryptedToken(JiraCredential $credential): string
    {
        return $this->encryption->decrypt($credential->getJiraApiToken());
    }

    public function delete(JiraCredential $credential): void
    {
        $this->repository->delete($credential);
    }

    public function findById(int $id): ?JiraCredential
    {
        return $this->repository->findById($id);
    }
}
