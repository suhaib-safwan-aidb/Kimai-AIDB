<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class LicenseClient implements LicenseClientInterface
{
    private const ACTIVATE_PATH = '/api/v1/licenses/activate';
    private const VALIDATE_PATH = '/api/v1/licenses/validate';
    private const DEACTIVATE_PATH = '/api/v1/licenses/deactivate';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $licenseServerUrl,
        private readonly string $apiKey,
    ) {
    }

    public function activate(string $licenseKey, string $instanceId): LicenseResponse
    {
        try {
            $response = $this->httpClient->request('POST', $this->buildUrl(self::ACTIVATE_PATH), [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                ],
                'json' => [
                    'license_key' => $licenseKey,
                    'instance_name' => $instanceId,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new LicenseException(sprintf('License server returned HTTP %d during activation.', $statusCode));
            }

            return LicenseResponse::fromArray($this->decodeResponsePayload($response, 'activation'));
        } catch (ExceptionInterface $e) {
            throw new LicenseException('License activation failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function verify(string $licenseKey, string $instanceId): LicenseResponse
    {
        try {
            $response = $this->httpClient->request('POST', $this->buildUrl(self::VALIDATE_PATH), [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                ],
                'json' => [
                    'license_key' => $licenseKey,
                    'instance_id' => $instanceId,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 422) {
                throw new LicenseInvalidInstanceException('License server returned HTTP 422 during verification: invalid instance_id.');
            }
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new LicenseException(sprintf('License server returned HTTP %d during verification.', $statusCode));
            }

            return LicenseResponse::fromArray($this->decodeResponsePayload($response, 'verification'));
        } catch (ExceptionInterface $e) {
            throw new LicenseException('License verification failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function deactivate(string $licenseKey, string $instanceId): void
    {
        try {
            $response = $this->httpClient->request('POST', $this->buildUrl(self::DEACTIVATE_PATH), [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                ],
                'json' => [
                    'license_key' => $licenseKey,
                    'instance_id' => $instanceId,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new LicenseException(sprintf('License server returned HTTP %d during deactivation.', $statusCode));
            }
        } catch (ExceptionInterface $e) {
            throw new LicenseException('License deactivation failed: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponsePayload(ResponseInterface $response, string $operation): array
    {
        $content = $response->getContent(false);

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new LicenseException(sprintf('License server returned invalid JSON during %s.', $operation), previous: $e);
        }

        if (is_string($payload)) {
            throw new LicenseException(sprintf('License server returned an unexpected string payload during %s: %s', $operation, $payload));
        }

        if (!is_array($payload)) {
            throw new LicenseException(sprintf('License server returned an unexpected payload type during %s.', $operation));
        }

        return $payload;
    }

    private function buildUrl(string $path): string
    {
        $baseUrl = rtrim($this->licenseServerUrl, '/');

        if (str_ends_with($baseUrl, '/api')) {
            $baseUrl = substr($baseUrl, 0, -4);
        }

        return $baseUrl . $path;
    }
}
