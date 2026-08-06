<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Repository;

use KimaiPlugin\KimaiJiraSyncBundle\Entity\JiraCredential;

interface JiraCredentialRepositoryInterface
{
    public function findByUserAndProject(int $userId, int $projectId): ?JiraCredential;

    /** @return JiraCredential[] */
    public function findByProject(int $projectId): array;

    /** @return JiraCredential[] */
    public function findByUser(int $userId): array;

    /** @return JiraCredential[] */
    public function findAll(): array;

    public function findById(int $id): ?JiraCredential;

    public function save(JiraCredential $credential): void;

    public function delete(JiraCredential $credential): void;

    /**
     * Returns the lowest project ID that has any Jira credential configured,
     * or null if no credentials exist yet.
     */
    public function findLowestProjectId(): ?int;
}
