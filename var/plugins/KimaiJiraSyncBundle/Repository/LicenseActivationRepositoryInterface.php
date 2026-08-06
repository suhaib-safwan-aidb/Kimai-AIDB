<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Repository;

use KimaiPlugin\KimaiJiraSyncBundle\Entity\LicenseActivation;

interface LicenseActivationRepositoryInterface
{
    public function findLatest(): ?LicenseActivation;

    public function save(LicenseActivation $activation): void;

    public function deleteAll(): void;
}
