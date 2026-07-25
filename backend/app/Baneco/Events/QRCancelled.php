<?php

declare(strict_types=1);

namespace App\Baneco\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QRCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $qrId
    ) {}
}
