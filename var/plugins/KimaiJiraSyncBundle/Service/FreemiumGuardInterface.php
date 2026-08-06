<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

interface FreemiumGuardInterface
{
    /**
     * Returns true if an active license is present.
     *
     * The per-project free-tier restriction has been removed: this always returns true.
     */
    public function isLicenseActive(): bool;

    /**
     * Returns true if the given project is allowed to use Jira sync.
     *
     * The plugin no longer limits how many projects can be synced, so this always returns true.
     */
    public function isProjectAllowed(int $projectId): bool;
}
