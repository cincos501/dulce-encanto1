<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class GenerateQRResponse
{
    public function __construct(
        public readonly int $responseCode,
        public readonly ?string $message,
        public readonly ?string $qrId = null,
        public readonly ?string $qrImage = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            responseCode: (int) ($data['responseCode'] ?? -1),
            message: $data['message'] ?? null,
            qrId: $data['qrId'] ?? null,
            qrImage: $data['qrImage'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'responseCode' => $this->responseCode,
            'message' => $this->message,
            'qrId' => $this->qrId,
            'qrImage' => $this->qrImage,
        ];
    }
}
