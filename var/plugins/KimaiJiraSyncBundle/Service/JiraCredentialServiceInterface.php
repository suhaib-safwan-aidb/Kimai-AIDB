<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

use KimaiPlugin\KimaiJiraSyncBundle\Entity\JiraCredential;

interface JiraCredentialServiceInterface
{
    public function findByUserAndProject(int $userId, int $projectId): ?JiraCredential;

    /** @return JiraCredential[] */
    public function findByProject(int $projectId): array;

    /** @return JiraCredential[] */
    public function findByUser(int $userId): array;

    /** @return JiraCredential[] */
    public function findAll(): array;

    public function save(int $userId, int $projectId, string $jiraUsername, string $plainApiToken): JiraCredential;

    public function getDecryptedToken(JiraCredential $credential): string;

    public function delete(JiraCredential $credential): void;

    public function findById(int $id): ?JiraCredential;
}
