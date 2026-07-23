<?php

declare(strict_types=1);

namespace App\Repositories;

interface WhatsAppSessionRepositoryInterface
{
    /**
     * Get the session context by phone number.
     */
    public function get(string $phone): ?array;

    /**
     * Save the session context for a phone number with a TTL (default 1 hour).
     */
    public function set(string $phone, array $data, int $ttl = 3600): void;

    /**
     * Delete the session context by phone number.
     */
    public function delete(string $phone): void;
}
