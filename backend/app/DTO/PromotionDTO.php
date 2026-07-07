<?php

declare(strict_types=1);

namespace App\DTO;

class PromotionDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly string $discount_type = 'percentage',
        public readonly float $discount = 0.00,
        public readonly string $start_date = '',
        public readonly string $end_date = '',
        public readonly bool $is_active = true
    ) {}

    /**
     * Create a DTO from request data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            discount_type: (string) ($data['discount_type'] ?? 'percentage'),
            discount: (float) $data['discount'],
            start_date: (string) $data['start_date'],
            end_date: (string) $data['end_date'],
            is_active: filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN)
        );
    }

    /**
     * Convert DTO attributes to an array for Eloquent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount' => $this->discount,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
        ];
    }
}
