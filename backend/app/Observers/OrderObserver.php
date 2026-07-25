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
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        if ($order->status === 'Pendiente') {
            $this->dispatchBanecoQR($order);
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // 1. Only trigger notification when status changes from anything else to "Listo"
        if ($order->isDirty('status') && $order->status === 'Listo' && $order->getOriginal('status') !== 'Listo') {
            $this->notificationService->notifyStatusReady($order);
        }

        // 2. Trigger Baneco QR generation when status transitions to "Pendiente"
        if ($order->isDirty('status') && $order->status === 'Pendiente' && $order->getOriginal('status') !== 'Pendiente') {
            $this->dispatchBanecoQR($order);
        }
    }

    /**
     * Dispatch the GenerateQRJob in the queue.
     */
    protected function dispatchBanecoQR(Order $order): void
    {
        $qrData = [
            'transactionId' => (string) $order->id,
            'accountCredit' => (string) config('baneco.account', 'placeholder_account_num'),
            'currency' => 'BOB',
            'amount' => (float) $order->total,
            'description' => "Pago del pedido #{$order->id}",
            'dueDate' => now()->addDays((int) config('baneco.qr_expiration_days', 1))->format('Y-m-d'),
            'singleUse' => true,
            'modifyAmount' => false
        ];

        \App\Baneco\Jobs\GenerateQRJob::dispatch($qrData);
    }
}
