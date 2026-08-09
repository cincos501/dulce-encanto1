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
        if (isset($data['response']) && is_array($data['response'])) {
            $data = $data['response'];
        }

        $statusQRCode = $data['statusQRCode'] ?? $data['statusQrCode'] ?? 0;

        $paymentQRData = null;
        if (isset($data['paymentQR'])) {
            $paymentQRData = $data['paymentQR'];
        } elseif (isset($data['payment'])) {
            $payments = $data['payment'];
            if (is_array($payments) && count($payments) > 0) {
                $paymentQRData = $payments[0];
            }
        }

        return new self(
            responseCode: (int) ($data['responseCode'] ?? 0),
            message: $data['message'] ?? null,
            statusQRCode: (int) $statusQRCode,
            paymentQR: $paymentQRData ? PaymentQR::fromArray((array) $paymentQRData) : null
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
