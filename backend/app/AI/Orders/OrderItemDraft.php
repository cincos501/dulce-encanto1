<?php

declare(strict_types=1);

namespace App\AI\Orders;

class OrderItemDraft
{
    /**
     * @param array<array{id: int, name: string, price: float}> $extras
     */
    public function __construct(
        public int $productId,
        public string $productName,
        public int $variantId,
        public string $variantName,
        public int $quantity,
        public float $unitPrice,
        public array $extras = [],
        public float $subtotal = 0.0
    ) {
        $this->calculateSubtotal();
    }

    /**
     * Calculate the subtotal for the item including extras.
     */
    public function calculateSubtotal(): void
    {
        $extrasPrice = 0.0;
        foreach ($this->extras as $extra) {
            $extrasPrice += (float) ($extra['price'] ?? 0.0);
        }
        $this->subtotal = ($this->unitPrice + $extrasPrice) * $this->quantity;
    }

    /**
     * Build draft item from array context.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) ($data['product_id'] ?? 0),
            productName: (string) ($data['product_name'] ?? ''),
            variantId: (int) ($data['variant_id'] ?? 0),
            variantName: (string) ($data['variant_name'] ?? ''),
            quantity: (int) ($data['quantity'] ?? 1),
            unitPrice: (float) ($data['unit_price'] ?? 0.0),
            extras: (array) ($data['extras'] ?? []),
            subtotal: (float) ($data['subtotal'] ?? 0.0)
        );
    }

    /**
     * Format draft item to array.
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'variant_id' => $this->variantId,
            'variant_name' => $this->variantName,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'extras' => $this->extras,
            'subtotal' => $this->subtotal,
        ];
    }
}
