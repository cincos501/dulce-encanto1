<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class GenerateQRRequest
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $accountCredit,
        public readonly string $currency,
        public readonly float $amount,
        public readonly ?string $description,
        public readonly string $dueDate,
        public readonly bool $singleUse,
        public readonly bool $modifyAmount,
        public readonly ?string $branchCode = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            accountCredit: (string) ($data['accountCredit'] ?? ''),
            currency: (string) ($data['currency'] ?? 'BOB'),
            amount: (float) ($data['amount'] ?? 0.0),
            description: $data['description'] ?? null,
            dueDate: (string) ($data['dueDate'] ?? ''),
            singleUse: (bool) ($data['singleUse'] ?? true),
            modifyAmount: (bool) ($data['modifyAmount'] ?? false),
            branchCode: $data['branchCode'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'accountCredit' => $this->accountCredit,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'description' => $this->description,
            'dueDate' => $this->dueDate,
            'singleUse' => $this->singleUse,
            'modifyAmount' => $this->modifyAmount,
            'branchCode' => $this->branchCode,
        ];
    }
}
