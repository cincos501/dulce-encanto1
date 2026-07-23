<?php

declare(strict_types=1);

namespace App\DTO;

class UpdateOrderStatusDTO
{
    public function __construct(
        public readonly string $status
    ) {}

    /**
     * Create a DTO from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: (string) $data['status']
        );
    }
}
