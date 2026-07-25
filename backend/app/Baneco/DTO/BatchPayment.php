<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class BatchPayment
{
    public function __construct(
        public readonly string $batchDetailId,
        public readonly float $amount,
        public readonly string $accountCode,
        public readonly string $accountTypeCode,
        public readonly string $bankCode,
        public readonly string $beneficiaryName,
        public readonly string $beneficaryDocId,
        public readonly string $beneficiaryPhone,
        public readonly string $beneficiaryEmail,
        public readonly string $note,
        public readonly AMLData $AMLData
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            batchDetailId: (string) ($data['batchDetailId'] ?? ''),
            amount: (float) ($data['amount'] ?? 0.0),
            accountCode: (string) ($data['accountCode'] ?? ''),
            accountTypeCode: (string) ($data['accountTypeCode'] ?? ''),
            bankCode: (string) ($data['bankCode'] ?? ''),
            beneficiaryName: (string) ($data['beneficiaryName'] ?? ''),
            beneficaryDocId: (string) ($data['beneficaryDocId'] ?? ''),
            beneficiaryPhone: (string) ($data['beneficiaryPhone'] ?? ''),
            beneficiaryEmail: (string) ($data['beneficiaryEmail'] ?? ''),
            note: (string) ($data['note'] ?? ''),
            AMLData: AMLData::fromArray($data['AMLData'] ?? [])
        );
    }

    public function toArray(): array
    {
        return [
            'batchDetailId' => $this->batchDetailId,
            'amount' => $this->amount,
            'accountCode' => $this->accountCode,
            'accountTypeCode' => $this->accountTypeCode,
            'bankCode' => $this->bankCode,
            'beneficiaryName' => $this->beneficiaryName,
            'beneficaryDocId' => $this->beneficaryDocId,
            'beneficiaryPhone' => $this->beneficiaryPhone,
            'beneficiaryEmail' => $this->beneficiaryEmail,
            'note' => $this->note,
            'AMLData' => $this->AMLData->toArray(),
        ];
    }
}
