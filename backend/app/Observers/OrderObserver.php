<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderNotificationService;

class OrderObserver
{
    public function __construct(
        protected OrderNotificationService $notificationService
    ) {}

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Only trigger notification when status changes from anything else to "Listo"
        if ($order->isDirty('status') && $order->status === 'Listo' && $order->getOriginal('status') !== 'Listo') {
            $this->notificationService->notifyStatusReady($order);
        }
    }
}
