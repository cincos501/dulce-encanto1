<?php

declare(strict_types=1);

namespace App\DTO;

class RecipeDTO
{
    /**
     * @param  array<int, RecipeItemDTO>  $items
     */
    public function __construct(
        public readonly int $product_variant_id,
        public readonly array $items = []
    ) {}

    /**
     * Create a DTO from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $items[] = RecipeItemDTO::fromArray($item);
            }
        }

        return new self(
            product_variant_id: (int) $data['product_variant_id'],
            items: $items
        );
    }
}
