<?php

declare(strict_types=1);

namespace App\DTO;

class SupplyDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $suppliers
     */
    public function __construct(
        public readonly string $name,
        public readonly string $unit,
        public readonly float $stock = 0.0000,
        public readonly float $minimum_stock = 0.0000,
        public readonly float $average_cost = 0.00,
        public readonly bool $is_active = true,
        public readonly array $suppliers = []
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
            unit: (string) $data['unit'],
            stock: isset($data['stock']) ? (float) $data['stock'] : 0.0000,
            minimum_stock: isset($data['minimum_stock']) ? (float) $data['minimum_stock'] : 0.0000,
            average_cost: isset($data['average_cost']) ? (float) $data['average_cost'] : 0.00,
            is_active: filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            suppliers: isset($data['suppliers']) && is_array($data['suppliers']) ? $data['suppliers'] : []
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
            'unit' => $this->unit,
            'stock' => $this->stock,
            'minimum_stock' => $this->minimum_stock,
            'average_cost' => $this->average_cost,
            'is_active' => $this->is_active,
        ];
    }
}
