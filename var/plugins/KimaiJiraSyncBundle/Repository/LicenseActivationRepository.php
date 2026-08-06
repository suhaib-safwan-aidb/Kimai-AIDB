<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KimaiPlugin\KimaiJiraSyncBundle\Entity\LicenseActivation;

/**
 * @extends ServiceEntityRepository<LicenseActivation>
 */
final class LicenseActivationRepository extends ServiceEntityRepository implements LicenseActivationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LicenseActivation::class);
    }

    public function findLatest(): ?LicenseActivation
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.activatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(LicenseActivation $activation): void
    {
        $em = $this->getEntityManager();
        $em->persist($activation);
        $em->flush();
    }

    public function deleteAll(): void
    {
        $this->createQueryBuilder('l')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
