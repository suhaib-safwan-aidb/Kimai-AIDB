<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Service;

use RuntimeException;

final class EncryptionService
{
    private const CIPHER = 'aes-256-cbc';
    private const IV_LENGTH = 16;

    private readonly string $key;

    public function __construct(string $appSecret)
    {
        // Derive a fixed-length 32-byte key from APP_SECRET
        $this->key = hash('sha256', $appSecret, binary: true);
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(self::IV_LENGTH);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $ciphertext): string
    {
        $decoded = base64_decode($ciphertext, strict: true);

        if ($decoded === false || strlen($decoded) <= self::IV_LENGTH) {
            throw new RuntimeException('Invalid ciphertext format');
        }

        $iv = substr($decoded, 0, self::IV_LENGTH);
        $encrypted = substr($decoded, self::IV_LENGTH);

        $decrypted = openssl_decrypt($encrypted, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed');
        }

        return $decrypted;
    }
}
