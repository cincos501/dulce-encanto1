<?php

declare(strict_types=1);

namespace App\DTO;

class StoreProductionDTO
{
    public function __construct(
        public readonly int $productVariantId,
        public readonly int $quantity,
        public readonly ?string $notes = null
    ) {}

    /**
     * Create DTO from request validated data array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productVariantId: (int) $data['product_variant_id'],
            quantity: (int) $data['quantity'],
            notes: $data['notes'] ?? null
        );
    }
}
