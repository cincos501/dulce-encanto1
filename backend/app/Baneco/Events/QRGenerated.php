<?php

declare(strict_types=1);

namespace App\Baneco\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QRGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $qrId,
        public readonly string $transactionId,
        public readonly float $amount,
        public readonly string $dueDate,
        public readonly string $qrImage
    ) {}
}
