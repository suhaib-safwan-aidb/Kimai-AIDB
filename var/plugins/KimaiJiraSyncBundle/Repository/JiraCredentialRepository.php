<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KimaiPlugin\KimaiJiraSyncBundle\Entity\JiraCredential;

/**
 * @extends ServiceEntityRepository<JiraCredential>
 */
final class JiraCredentialRepository extends ServiceEntityRepository implements JiraCredentialRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JiraCredential::class);
    }

    public function findByUserAndProject(int $userId, int $projectId): ?JiraCredential
    {
        return $this->findOneBy(['userId' => $userId, 'projectId' => $projectId]);
    }

    /** @return JiraCredential[] */
    public function findByProject(int $projectId): array
    {
        return $this->findBy(['projectId' => $projectId]);
    }

    /** @return JiraCredential[] */
    public function findByUser(int $userId): array
    {
        return $this->findBy(['userId' => $userId]);
    }

    /** @return JiraCredential[] */
    public function findAll(): array
    {
        return parent::findAll();
    }

    public function findById(int $id): ?JiraCredential
    {
        return $this->find($id);
    }

    public function save(JiraCredential $credential): void
    {
        $em = $this->getEntityManager();
        $em->persist($credential);
        $em->flush();
    }

    public function delete(JiraCredential $credential): void
    {
        $em = $this->getEntityManager();
        $em->remove($credential);
        $em->flush();
    }

    public function findLowestProjectId(): ?int
    {
        $result = $this->createQueryBuilder('c')
            ->select('MIN(c.projectId)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (int) $result : null;
    }
}
