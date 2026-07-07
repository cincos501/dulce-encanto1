<?php

declare(strict_types=1);

namespace App\DTO;

class ProductDTO
{
    public function __construct(
        public readonly int $category_id,
        public readonly string $name,
        public readonly ?string $description = null,
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
            category_id: (int) $data['category_id'],
            name: (string) $data['name'],
            description: isset($data['description']) ? (string) $data['description'] : null,
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
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}
