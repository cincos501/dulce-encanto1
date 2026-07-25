<?php

declare(strict_types=1);

namespace App\Baneco\DTO;

class AccountDetail
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $date,
        public readonly string $time,
        public readonly string $documentNumber,
        public readonly string $transactionType,
        public readonly float $amount,
        public readonly string $description,
        public readonly string $clienteNote
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            date: (string) ($data['date'] ?? ''),
            time: (string) ($data['time'] ?? ''),
            documentNumber: (string) ($data['documentNumber'] ?? ''),
            transactionType: (string) ($data['transactionType'] ?? ''),
            amount: (float) ($data['amount'] ?? 0.0),
            description: (string) ($data['description'] ?? ''),
            clienteNote: (string) ($data['clienteNote'] ?? '')
        );
    }

    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'date' => $this->date,
            'time' => $this->time,
            'documentNumber' => $this->documentNumber,
            'transactionType' => $this->transactionType,
            'amount' => $this->amount,
            'description' => $this->description,
            'clienteNote' => $this->clienteNote,
        ];
    }
}
