<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class PaidQRResponse
{
    /**
     * @param PaymentQR[] $paymentList
     */
    public function __construct(
        public readonly int $responseCode,
        public readonly ?string $message,
        public readonly array $paymentList = []
    ) {}

    public static function fromArray(array $data): self
    {
        $list = [];
        $rawList = $data['paymentList'] ?? [];
        foreach ($rawList as $item) {
            $list[] = PaymentQR::fromArray($item);
        }

        return new self(
            responseCode: (int) ($data['responseCode'] ?? 0),
            message: $data['message'] ?? null,
            paymentList: $list
        );
    }

    public function toArray(): array
    {
        return [
            'responseCode' => $this->responseCode,
            'message' => $this->message,
            'paymentList' => array_map(fn(PaymentQR $p) => $p->toArray(), $this->paymentList),
        ];
    }
}
