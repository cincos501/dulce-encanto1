<?php

declare(strict_types=1);

namespace App\DTO;

class ProductVariantDTO
{
    public function __construct(
        public readonly int $product_id,
        public readonly string $name,
        public readonly string $sku = '',
        public readonly float $base_price = 0.00,
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
            product_id: (int) $data['product_id'],
            name: (string) $data['name'],
            sku: (string) ($data['sku'] ?? ''),
            base_price: (float) $data['base_price'],
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
        $arr = [
            'product_id' => $this->product_id,
            'name' => $this->name,
            'base_price' => $this->base_price,
            'is_active' => $this->is_active,
        ];

        if ($this->sku !== '') {
            $arr['sku'] = $this->sku;
        }

        return $arr;
    }
}
