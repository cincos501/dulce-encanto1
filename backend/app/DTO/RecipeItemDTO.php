<?php

declare(strict_types=1);

namespace App\DTO;

class RecipeItemDTO
{
    public function __construct(
        public readonly int $supply_id,
        public readonly float $quantity,
        public readonly string $unit,
        public readonly ?string $observation = null
    ) {}

    /**
     * Create a DTO from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            supply_id: (int) $data['supply_id'],
            quantity: (float) $data['quantity'],
            unit: (string) $data['unit'],
            observation: isset($data['observation']) ? (string) $data['observation'] : null
        );
    }
}
