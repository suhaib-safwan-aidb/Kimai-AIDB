<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Jira;

final readonly class JiraWorklogData
{
    public function __construct(
        public string $issueKey,
        public \DateTimeInterface $started,
        public int $timeSpentSeconds,
        public string $comment,
    ) {
    }
}
