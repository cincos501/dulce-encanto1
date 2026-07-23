<?php

declare(strict_types=1);

namespace App\Models;

class WhatsAppSession
{
    public string $phone;
    public string $name;
    public string $lastMessage;
    public string $step;
    public array $orderData;
    public array $history;
    public string $updatedAt;

    public function __construct(array $attributes = [])
    {
        $this->phone = $attributes['phone'] ?? '';
        $this->name = $attributes['name'] ?? '';
        $this->lastMessage = $attributes['last_message'] ?? '';
        $this->step = $attributes['step'] ?? 'idle';
        $this->orderData = $attributes['order_data'] ?? [];
        $this->history = $attributes['history'] ?? [];
        $this->updatedAt = $attributes['updated_at'] ?? now()->toIso8601String();
    }

    /**
     * Convert the session to an array representation.
     */
    public function toArray(): array
    {
        return [
            'phone' => $this->phone,
            'name' => $this->name,
            'last_message' => $this->lastMessage,
            'step' => $this->step,
            'order_data' => $this->orderData,
            'history' => $this->history,
            'updated_at' => $this->updatedAt,
        ];
    }
}
