<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KimaiPlugin\KimaiJiraSyncBundle\Repository\LicenseActivationRepository;

#[ORM\Entity(repositoryClass: LicenseActivationRepository::class)]
#[ORM\Table(name: 'kimai2_jira_sync_license')]
class LicenseActivation
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_INVALID = 'invalid';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $licenseKey;

    #[ORM\Column(type: 'string', length: 500)]
    private string $instanceId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $activatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastVerifiedAt = null;

    public function __construct(
        string $licenseKey,
        string $instanceId,
        string $status,
        ?\DateTimeImmutable $expiresAt = null,
    ) {
        $this->licenseKey = $licenseKey;
        $this->instanceId = $instanceId;
        $this->status = $status;
        $this->expiresAt = $expiresAt;
        $this->activatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLicenseKey(): string
    {
        return $this->licenseKey;
    }

    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getActivatedAt(): \DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function getLastVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->lastVerifiedAt;
    }

    public function setLastVerifiedAt(?\DateTimeImmutable $lastVerifiedAt): void
    {
        $this->lastVerifiedAt = $lastVerifiedAt;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
