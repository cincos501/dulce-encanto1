<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SupplyStockLow;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Log;

class HandleSupplyStockLow
{
    public function __construct(
        protected OrderNotificationService $notificationService
    ) {}

    public function handle(SupplyStockLow $event): void
    {
        Log::info("HandleSupplyStockLow listener triggered", [
            'supply_id' => $event->supply->id,
            'name' => $event->supply->name
        ]);

        $this->notificationService->notifyLowSupply($event->supply);
    }
}
