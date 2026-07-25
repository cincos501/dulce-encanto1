<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class NotificationRequest
{
    public function __construct(
        public readonly ?PaymentQR $paymentQR = null,
        public readonly ?string $bankBatchId = null,
        public readonly ?string $batchId = null,
        public readonly ?string $batchDetailId = null,
        public readonly ?string $status = null,
        public readonly ?string $transactionIdDebit = null,
        public readonly ?string $transactionIdCredit = null
    ) {}

    public static function fromArray(array $data): self
    {
        // Check if payload contains batch fields or QR fields
        if (isset($data['qrId']) || isset($data['transactionId'])) {
            return new self(
                paymentQR: PaymentQR::fromArray($data)
            );
        }

        return new self(
            bankBatchId: $data['bankBatchId'] ?? null,
            batchId: $data['batchId'] ?? null,
            batchDetailId: $data['batchDetailId'] ?? null,
            status: $data['status'] ?? null,
            transactionIdDebit: $data['transactionIdDebit'] ?? null,
            transactionIdCredit: $data['transactionIdCredit'] ?? null
        );
    }

    public function toArray(): array
    {
        if ($this->paymentQR) {
            return $this->paymentQR->toArray();
        }

        return [
            'bankBatchId' => $this->bankBatchId,
            'batchId' => $this->batchId,
            'batchDetailId' => $this->batchDetailId,
            'status' => $this->status,
            'transactionIdDebit' => $this->transactionIdDebit,
            'transactionIdCredit' => $this->transactionIdCredit,
        ];
    }
}
