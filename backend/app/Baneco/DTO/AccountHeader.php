<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class AccountHeader
{
    public function __construct(
        public readonly string $accountCode,
        public readonly string $accountTypeCode,
        public readonly string $productName,
        public readonly string $status,
        public readonly string $currency,
        public readonly float $balance,
        public readonly float $balanceReserved,
        public readonly float $balanceRetained,
        public readonly float $balanceAvailable
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            accountCode: (string) ($data['accountCode'] ?? ''),
            accountTypeCode: (string) ($data['accountTypeCode'] ?? ''),
            productName: (string) ($data['productName'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            currency: (string) ($data['currency'] ?? ''),
            balance: (float) ($data['balance'] ?? 0.0),
            balanceReserved: (float) ($data['balanceReserved'] ?? 0.0),
            balanceRetained: (float) ($data['balanceRetained'] ?? 0.0),
            balanceAvailable: (float) ($data['balanceAvailable'] ?? 0.0)
        );
    }

    public function toArray(): array
    {
        return [
            'accountCode' => $this->accountCode,
            'accountTypeCode' => $this->accountTypeCode,
            'productName' => $this->productName,
            'status' => $this->status,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'balanceReserved' => $this->balanceReserved,
            'balanceRetained' => $this->balanceRetained,
            'balanceAvailable' => $this->balanceAvailable,
        ];
    }
}
