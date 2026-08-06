<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Jira;

interface JiraClientInterface
{
    /**
     * Add a worklog to a Jira issue.
     *
     * @throws JiraClientException
     */
    public function addWorklog(JiraWorklogData $data): string;

    /**
     * Update an existing worklog on a Jira issue.
     *
     * @throws JiraClientException
     */
    public function updateWorklog(string $issueKey, string $worklogId, JiraWorklogData $data): void;

    /**
     * Delete a worklog from a Jira issue.
     *
     * @throws JiraClientException
     */
    public function deleteWorklog(string $issueKey, string $worklogId): void;

    /**
     * Search Jira issues using JQL.
     *
     * @return array<int, array<string, mixed>>
     * @throws JiraClientException
     */
    public function searchIssues(string $jql, int $maxResults = 1000): array;

    /**
     * Get a specific worklog by ID.
     *
     * @return array<string, mixed>
     * @throws JiraClientException
     */
    public function getWorklog(string $issueKey, string $worklogId): array;
}
