<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Jira;

/**
 * Creates JiraClient instances with the given credentials.
 * Encapsulates instantiation to allow mocking in unit tests.
 */
final class JiraClientFactory implements JiraClientFactoryInterface
{
    public function create(string $instanceUrl, string $username, string $apiToken): JiraClientInterface
    {
        return new JiraClient($instanceUrl, $username, $apiToken);
    }
}
