<?php

declare(strict_types=1);

namespace App\Baneco\Services;

use App\Baneco\Contracts\EncryptionServiceInterface;
use Illuminate\Support\Facades\Log;

class Aes256EncryptionService implements EncryptionServiceInterface
{
    protected string $key;
    protected string $cipher = 'aes-256-cbc';

    public function __construct()
    {
        // Pad or truncate key to 32 bytes for AES-256.
        $rawKey = (string) config('baneco.aes_key', 'placeholder_key_32_bytes_dev_only');
        $this->key = substr(str_pad($rawKey, 32, "\0"), 0, 32);
    }

    /**
     * Encrypt data using AES-256-CBC.
     */
    public function encrypt(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt(
            $text,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            Log::channel('baneco')->error('Aes256EncryptionService: Encryption failed.');
            return '';
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data using AES-256-CBC.
     */
    public function decrypt(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $decoded = base64_decode($text, true);
        if ($decoded === false) {
            Log::channel('baneco')->error('Aes256EncryptionService: Decryption failed, invalid base64 input.');
            return '';
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        if (strlen($decoded) < $ivLength) {
            Log::channel('baneco')->error('Aes256EncryptionService: Decryption failed, decoded text too short for IV.');
            return '';
        }

        $iv = substr($decoded, 0, $ivLength);
        $ciphertext = substr($decoded, $ivLength);

        $decrypted = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            Log::channel('baneco')->error('Aes256EncryptionService: Decryption failed.');
            return '';
        }

        return $decrypted;
    }
}
