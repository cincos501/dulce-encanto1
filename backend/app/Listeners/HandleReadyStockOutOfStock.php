<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ReadyStockOutOfStock;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Log;

class HandleReadyStockOutOfStock
{
    public function __construct(
        protected OrderNotificationService $notificationService
    ) {}

    public function handle(ReadyStockOutOfStock $event): void
    {
        Log::info("HandleReadyStockOutOfStock listener triggered", [
            'variant_id' => $event->variant->id,
            'sku' => $event->variant->sku
        ]);

        $this->notificationService->notifyReadyStockOutOfStock($event->variant);
    }
}
