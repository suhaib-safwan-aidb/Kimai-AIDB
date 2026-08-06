<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KimaiPlugin\KimaiJiraSyncBundle\Repository\JiraCredentialRepository;

#[ORM\Entity(repositoryClass: JiraCredentialRepository::class)]
#[ORM\Table(name: 'kimai2_jira_credentials')]
#[ORM\UniqueConstraint(name: 'jira_cred_user_project', columns: ['user_id', 'project_id'])]
class JiraCredential
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'integer')]
    private int $projectId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $jiraUsername;

    #[ORM\Column(type: 'text')]
    private string $jiraApiToken;

    public function __construct(int $userId, int $projectId, string $jiraUsername, string $jiraApiToken)
    {
        $this->userId = $userId;
        $this->projectId = $projectId;
        $this->jiraUsername = $jiraUsername;
        $this->jiraApiToken = $jiraApiToken;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getJiraUsername(): string
    {
        return $this->jiraUsername;
    }

    public function setJiraUsername(string $jiraUsername): void
    {
        $this->jiraUsername = $jiraUsername;
    }

    public function getJiraApiToken(): string
    {
        return $this->jiraApiToken;
    }

    public function setJiraApiToken(string $jiraApiToken): void
    {
        $this->jiraApiToken = $jiraApiToken;
    }
}
