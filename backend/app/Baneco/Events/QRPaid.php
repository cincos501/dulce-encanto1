<?php

declare(strict_types=1);

namespace App\Baneco\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QRPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $qrId,
        public readonly string $transactionId,
        public readonly string $paymentDate,
        public readonly string $paymentTime,
        public readonly float $amount
    ) {}
}
