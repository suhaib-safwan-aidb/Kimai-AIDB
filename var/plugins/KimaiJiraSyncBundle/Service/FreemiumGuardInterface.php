<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

interface FreemiumGuardInterface
{
    /**
     * Returns true if an active license is present.
     */
    public function isLicenseActive(): bool;

    /**
     * Returns true if the given project is allowed to use Jira sync.
     *
     * With an active license all projects are allowed.
     * Without a license only the first (lowest-ID) configured project is allowed for free.
     */
    public function isProjectAllowed(int $projectId): bool;
}
