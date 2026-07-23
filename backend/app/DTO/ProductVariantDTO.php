<?php

declare(strict_types=1);

namespace App\DTO;

class ProductVariantDTO
{
    public function __construct(
        public readonly int $product_id,
        public readonly string $name,
        public readonly string $sku = '',
        public readonly float $price = 0.00,
        public readonly ?int $serves_people = null,
        public readonly bool $is_active = true,
        public readonly array $extras = []
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
            price: (float) $data['price'],
            serves_people: isset($data['serves_people']) ? (int) $data['serves_people'] : null,
            is_active: filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            extras: (array) ($data['extras'] ?? [])
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
            'price' => $this->price,
            'serves_people' => $this->serves_people,
            'is_active' => $this->is_active,
        ];

        if ($this->sku !== '') {
            $arr['sku'] = $this->sku;
        }

        return $arr;
    }
}
