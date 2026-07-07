<?php

declare(strict_types=1);

namespace App\DTO;

class ProductImageDTO
{
    public function __construct(
        public readonly int $product_variant_id,
        public readonly string $image_path,
        public readonly bool $is_primary = false
    ) {}

    /**
     * Convert DTO attributes to an array for Eloquent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_variant_id' => $this->product_variant_id,
            'image_url' => $this->image_path,
            'is_primary' => $this->is_primary,
        ];
    }
}
