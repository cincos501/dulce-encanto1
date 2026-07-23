<?php

declare(strict_types=1);

namespace App\DTO;

class StoreOrderDTO
{
    /**
     * @param StoreOrderItemDTO[] $items
     */
    public function __construct(
        public string $customerName,
        public string $customerPhone,
        public string $deliveryType,
        public ?string $address,
        public ?string $observations,
        public string $deliveryDate,
        public array $items
    ) {}

    public static function fromArray(array $data): self
    {
        $items = array_map(
            fn(array $item) => StoreOrderItemDTO::fromArray($item),
            $data['items']
        );

        // Combine date and time
        $deliveryDateTime = "{$data['delivery_date']} {$data['delivery_time']}:00";

        return new self(
            customerName: $data['customer_name'],
            customerPhone: $data['customer_phone'],
            deliveryType: $data['delivery_type'],
            address: $data['address'] ?? null,
            observations: $data['observations'] ?? null,
            deliveryDate: $deliveryDateTime,
            items: $items
        );
    }
}
