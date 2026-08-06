<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Jira;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class JiraClient implements JiraClientInterface
{
    private readonly HttpClientInterface $http;

    public function __construct(
        string $instanceUrl,
        string $username,
        string $apiToken,
    ) {
        $this->http = HttpClient::create([
            'base_uri' => rtrim($instanceUrl, '/') . '/rest/api/3/',
            'auth_basic' => [$username, $apiToken],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    public function addWorklog(JiraWorklogData $data): string
    {
        try {
            $response = $this->http->request('POST', "issue/{$data->issueKey}/worklog", [
                'json' => $this->buildWorklogBody($data),
            ]);

            /** @var array{id: string} $body */
            $body = $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new JiraClientException(
                "Failed to add worklog to {$data->issueKey}: {$e->getMessage()}",
                previous: $e,
            );
        } catch (\Throwable $e) {
            throw new JiraClientException(
                "Failed to add worklog to {$data->issueKey}: {$e->getMessage()}",
                previous: $e,
            );
        }

        return $body['id'];
    }

    public function updateWorklog(string $issueKey, string $worklogId, JiraWorklogData $data): void
    {
        try {
            $this->http->request('PUT', "issue/{$issueKey}/worklog/{$worklogId}", [
                'json' => $this->buildWorklogBody($data),
            ])->getContent();
        } catch (TransportExceptionInterface $e) {
            throw new JiraClientException(
                "Failed to update worklog {$worklogId} on {$issueKey}: {$e->getMessage()}",
                previous: $e,
            );
        } catch (\Throwable $e) {
            throw new JiraClientException(
                "Failed to update worklog {$worklogId} on {$issueKey}: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    public function deleteWorklog(string $issueKey, string $worklogId): void
    {
        try {
            $this->http->request('DELETE', "issue/{$issueKey}/worklog/{$worklogId}")->getContent();
        } catch (TransportExceptionInterface $e) {
            throw new JiraClientException(
                "Failed to delete worklog {$worklogId} on {$issueKey}: {$e->getMessage()}",
                previous: $e,
            );
        } catch (\Throwable $e) {
            throw new JiraClientException(
                "Failed to delete worklog {$worklogId} on {$issueKey}: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function searchIssues(string $jql, int $maxResults = 1000): array
    {
        $issues = [];
        $nextPageToken = null;

        do {
            $payload = [
                'jql'        => $jql,
                'maxResults' => $maxResults,
                'fields'     => ['summary', 'status', 'issuetype', 'assignee'],
            ];

            if ($nextPageToken !== null) {
                $payload['nextPageToken'] = $nextPageToken;
            }

            try {
                /** @var array{issues: array<int, array<string, mixed>>, nextPageToken?: string, isLast?: bool} $body */
                $body = $this->http->request('POST', 'search/jql', [
                    'json' => $payload,
                ])->toArray();
            } catch (TransportExceptionInterface $e) {
                throw new JiraClientException(
                    "JQL search failed: {$e->getMessage()}",
                    previous: $e,
                );
            } catch (\Throwable $e) {
                throw new JiraClientException(
                    "JQL search failed: {$e->getMessage()}",
                    previous: $e,
                );
            }

            $batch = $body['issues'];
            $issues = array_merge($issues, $batch);
            $nextPageToken = $body['nextPageToken'] ?? null;
        } while ($nextPageToken !== null);

        return $issues;
    }

    /** @return array<string, mixed> */
    public function getWorklog(string $issueKey, string $worklogId): array
    {
        try {
            /** @var array<string, mixed> $body */
            $body = $this->http->request('GET', "issue/{$issueKey}/worklog/{$worklogId}")->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new JiraClientException(
                "Failed to get worklog {$worklogId} on {$issueKey}: {$e->getMessage()}",
                previous: $e,
            );
        } catch (\Throwable $e) {
            throw new JiraClientException(
                "Failed to get worklog {$worklogId} on {$issueKey}: {$e->getMessage()}",
                previous: $e,
            );
        }

        return $body;
    }

    /** @return array<int, array{key: string, name: string}> */
    public function searchProjects(string $query, int $maxResults = 20): array
    {
        try {
            /** @var array{values: array<int, array<string, mixed>>} $body */
            $body = $this->http->request('GET', 'project/search', [
                'query' => [
                    'query'      => $query,
                    'maxResults' => $maxResults,
                ],
            ])->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new JiraClientException(
                "Project search failed for query \"{$query}\": {$e->getMessage()}",
                previous: $e,
            );
        } catch (\Throwable $e) {
            throw new JiraClientException(
                "Project search failed for query \"{$query}\": {$e->getMessage()}",
                previous: $e,
            );
        }

        $projects = [];
        foreach ($body['values'] ?? [] as $project) {
            if (!isset($project['key'], $project['name'])) {
                continue;
            }

            $projects[] = ['key' => (string) $project['key'], 'name' => (string) $project['name']];
        }

        return $projects;
    }

    /** @return array<string, mixed> */
    private function buildWorklogBody(JiraWorklogData $data): array
    {
        $body = [
            'started'          => $data->started->format('Y-m-d\TH:i:s.000O'),
            'timeSpentSeconds' => $data->timeSpentSeconds,
        ];

        if ($data->comment !== '') {
            $body['comment'] = $this->toAdf($data->comment);
        }

        return $body;
    }

    /** @return array<string, mixed> */
    private function toAdf(string $text): array
    {
        return [
            'type'    => 'doc',
            'version' => 1,
            'content' => [
                [
                    'type'    => 'paragraph',
                    'content' => [['type' => 'text', 'text' => $text]],
                ],
            ],
        ];
    }
}
