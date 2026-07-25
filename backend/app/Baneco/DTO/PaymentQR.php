<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class PaymentQR
{
    public function __construct(
        public readonly string $qrId,
        public readonly string $transactionId,
        public readonly string $paymentDate,
        public readonly string $paymentTime,
        public readonly string $currency,
        public readonly float $amount,
        public readonly ?string $senderBankCode = null,
        public readonly ?string $senderName = null,
        public readonly ?string $senderDocumentId = null,
        public readonly ?string $senderAccount = null,
        public readonly ?string $description = null,
        public readonly ?string $branchCode = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            qrId: (string) ($data['qrId'] ?? ''),
            transactionId: (string) ($data['transactionId'] ?? ''),
            paymentDate: (string) ($data['paymentDate'] ?? ''),
            paymentTime: (string) ($data['paymentTime'] ?? ''),
            currency: (string) ($data['currency'] ?? ''),
            amount: (float) ($data['amount'] ?? 0.0),
            senderBankCode: $data['senderBankCode'] ?? null,
            senderName: $data['senderName'] ?? null,
            senderDocumentId: $data['senderDocumentId'] ?? null,
            senderAccount: $data['senderAccount'] ?? null,
            description: $data['description'] ?? null,
            branchCode: $data['branchCode'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'qrId' => $this->qrId,
            'transactionId' => $this->transactionId,
            'paymentDate' => $this->paymentDate,
            'paymentTime' => $this->paymentTime,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'senderBankCode' => $this->senderBankCode,
            'senderName' => $this->senderName,
            'senderDocumentId' => $this->senderDocumentId,
            'senderAccount' => $this->senderAccount,
            'description' => $this->description,
            'branchCode' => $this->branchCode,
        ];
    }
}
