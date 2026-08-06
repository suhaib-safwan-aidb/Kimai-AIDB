<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Jira;

interface JiraClientFactoryInterface
{
    public function create(string $instanceUrl, string $username, string $apiToken): JiraClientInterface;
}
