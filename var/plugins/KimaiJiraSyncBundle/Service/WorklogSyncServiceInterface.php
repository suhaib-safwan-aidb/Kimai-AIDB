<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

use App\Entity\Timesheet;

interface WorklogSyncServiceInterface
{
    public function syncCreated(Timesheet $timesheet): void;

    public function syncUpdated(Timesheet $timesheet, ?string $previousIssueKey = null): void;

    public function syncDeleted(Timesheet $timesheet): void;
}
