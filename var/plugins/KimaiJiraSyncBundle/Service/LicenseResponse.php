<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

final class LicenseResponse
{
    public function __construct(
        public readonly bool $valid,
        public readonly string $status,
        public readonly ?\DateTimeImmutable $expiresAt,
        public readonly ?string $instanceId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $valid = self::resolveValid($data);
        $status = self::resolveStatus($data, $valid);
        $expiresAt = self::resolveExpiresAt($data);
        $instanceId = self::resolveInstanceId($data);

        return new self(
            valid: $valid,
            status: $status,
            expiresAt: $expiresAt,
            instanceId: $instanceId,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function resolveValid(array $data): bool
    {
        if (array_key_exists('valid', $data)) {
            return (bool) $data['valid'];
        }

        if (array_key_exists('activated', $data)) {
            return (bool) $data['activated'];
        }

        return false;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function resolveStatus(array $data, bool $valid): string
    {
        foreach ([
            $data['status'] ?? null,
            self::getNestedValue($data, ['meta', 'status']),
            self::getNestedValue($data, ['license_key', 'status']),
        ] as $status) {
            if (is_string($status) && $status !== '') {
                return $status;
            }
        }

        return $valid ? 'active' : 'invalid';
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function resolveExpiresAt(array $data): ?\DateTimeImmutable
    {
        foreach ([
            $data['expires_at'] ?? null,
            self::getNestedValue($data, ['meta', 'expires_at']),
            self::getNestedValue($data, ['meta', 'expiresAt']),
            self::getNestedValue($data, ['license_key', 'expires_at']),
            self::getNestedValue($data, ['license_key', 'expiresAt']),
        ] as $expiresAt) {
            if (!is_string($expiresAt) || $expiresAt === '') {
                continue;
            }

            try {
                return new \DateTimeImmutable($expiresAt);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function resolveInstanceId(array $data): ?string
    {
        foreach ([
            $data['instance_id'] ?? null,
            self::getNestedValue($data, ['instance', 'id']),
        ] as $instanceId) {
            if (is_string($instanceId) && $instanceId !== '') {
                return $instanceId;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $path
     */
    private static function getNestedValue(array $data, array $path): mixed
    {
        $current = $data;

        foreach ($path as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }

            $current = $current[$key];
        }

        return $current;
    }
}
