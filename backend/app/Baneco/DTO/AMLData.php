<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class AMLData
{
    public function __construct(
        public readonly array $data = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
