<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class StatusQRResponse
{
    public function __construct(
        public readonly int $responseCode,
        public readonly ?string $message,
        public readonly int $statusQRCode,
        public readonly ?PaymentQR $paymentQR = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            responseCode: (int) ($data['responseCode'] ?? 0),
            message: $data['message'] ?? null,
            statusQRCode: (int) ($data['statusQRCode'] ?? 0),
            paymentQR: isset($data['paymentQR']) ? PaymentQR::fromArray($data['paymentQR']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'responseCode' => $this->responseCode,
            'message' => $this->message,
            'statusQRCode' => $this->statusQRCode,
            'paymentQR' => $this->paymentQR?->toArray(),
        ];
    }
}
