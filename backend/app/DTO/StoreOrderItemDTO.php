<?php

declare(strict_types=1);

namespace App\DTO;

class StoreOrderItemDTO
{
    /**
     * @param int[] $extras
     */
    public function __construct(
        public int $productVariantId,
        public int $quantity,
        public array $extras
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productVariantId: (int) $data['product_variant_id'],
            quantity: (int) $data['quantity'],
            extras: $data['extras'] ?? []
        );
    }
}
