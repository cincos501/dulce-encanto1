<?php

declare(strict_types=1);

namespace App\DTO;

class SupplierDTO
{
    public function __construct(
        public readonly string $business_name,
        public readonly string $phone,
        public readonly ?string $email = null,
        public readonly ?string $address = null,
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
            business_name: (string) $data['business_name'],
            phone: (string) $data['phone'],
            email: isset($data['email']) ? (string) $data['email'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
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
        return [
            'business_name' => $this->business_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'is_active' => $this->is_active,
        ];
    }
}
