<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;

class RedisWhatsAppSessionRepository implements WhatsAppSessionRepositoryInterface
{
    protected string $prefix = 'wa_session:';

    /**
     * Get the session context by phone number.
     */
    public function get(string $phone): ?array
    {
        $key = $this->prefix . $phone;
        $data = Redis::get($key);

        return $data ? json_decode((string) $data, true) : null;
    }

    /**
     * Save the session context for a phone number with a TTL.
     */
    public function set(string $phone, array $data, int $ttl = 3600): void
    {
        $key = $this->prefix . $phone;
        Redis::setex($key, $ttl, json_encode($data));
    }

    /**
     * Delete the session context by phone number.
     */
    public function delete(string $phone): void
    {
        $key = $this->prefix . $phone;
        Redis::del($key);
    }
}
