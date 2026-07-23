<?php

declare(strict_types=1);

namespace App\AI\Orders;

class OrderDraft
{
    /**
     * @param array<OrderItemDraft> $items
     */
    public function __construct(
        public string $customerPhone,
        public array $items = [],
        public float $subtotal = 0.0,
        public float $total = 0.0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null
    ) {
        $this->createdAt = $createdAt ?? now()->toIso8601String();
        $this->updatedAt = $updatedAt ?? now()->toIso8601String();
        $this->calculateTotals();
    }

    /**
     * Recalculate subtotal and total for the draft.
     */
    public function calculateTotals(): void
    {
        $subtotal = 0.0;
        foreach ($this->items as $item) {
            $item->calculateSubtotal();
            $subtotal += $item->subtotal;
        }
        $this->subtotal = $subtotal;
        $this->total = $subtotal;
        $this->updatedAt = now()->toIso8601String();
    }

    /**
     * Build draft from array context.
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        foreach ((array) ($data['items'] ?? []) as $itemData) {
            $items[] = OrderItemDraft::fromArray($itemData);
        }

        return new self(
            customerPhone: (string) ($data['customer_phone'] ?? ''),
            items: $items,
            subtotal: (float) ($data['subtotal'] ?? 0.0),
            total: (float) ($data['total'] ?? 0.0),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null
        );
    }

    /**
     * Format draft to array.
     */
    public function toArray(): array
    {
        return [
            'customer_phone' => $this->customerPhone,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
