<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ProductVariant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReadyStockOutOfStock
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ProductVariant $variant
    ) {}
}
