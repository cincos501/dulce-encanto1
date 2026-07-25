<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class CancelQRRequest
{
    public function __construct(
        public readonly string $qrId
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            qrId: (string) ($data['qrId'] ?? '')
        );
    }

    public function toArray(): array
    {
        return [
            'qrId' => $this->qrId
        ];
    }
}
