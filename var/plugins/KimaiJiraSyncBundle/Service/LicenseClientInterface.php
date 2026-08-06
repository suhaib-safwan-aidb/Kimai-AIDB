<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

interface LicenseClientInterface
{
    public function activate(string $licenseKey, string $instanceId): LicenseResponse;

    public function verify(string $licenseKey, string $instanceId): LicenseResponse;

    public function deactivate(string $licenseKey, string $instanceId): void;
}
