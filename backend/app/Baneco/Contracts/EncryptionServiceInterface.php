<?php

declare(strict_types=1);

namespace App\Baneco\Contracts;

interface EncryptionServiceInterface
{
    /**
     * Encrypt the given text using AES-256 bits.
     */
    public function encrypt(string $text): string;

    /**
     * Decrypt the given ciphertext using AES-256 bits.
     */
    public function decrypt(string $text): string;
}
