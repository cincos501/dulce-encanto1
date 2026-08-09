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
        if ($order->isDirty('status')) {
            $oldStatus = $order->getOriginal('status');
            $newStatus = $order->status;

            // 1. Order changed to "Listo" (Notify customer and admin)
            if ($newStatus === 'Listo' && $oldStatus !== 'Listo') {
                $this->notificationService->notifyStatusReady($order);
                $this->notificationService->notifyAdminOrderReady($order);
            }

            // 2. Order changed to "Cancelado" (Notify customer)
            if ($newStatus === 'Cancelado' && $oldStatus !== 'Cancelado') {
                $this->notificationService->notifyStatusCancelled($order);
            }

            // 3. Order changed to "Confirmado" (Notify repostero and client)
            if ($newStatus === 'Confirmado' && $oldStatus !== 'Confirmado') {
                $this->notificationService->notifyReposteroOrderConfirmed($order);
                $this->notificationService->notifyClientOrderConfirmed($order);
            }

            // 4. Order changed to "En preparación" (Notify repostero and client)
            if ($newStatus === 'En preparación' && $oldStatus !== 'En preparación') {
                $this->notificationService->notifyReposteroOrderInPreparation($order);
                $this->notificationService->notifyClientOrderInPreparation($order);
            }

            // 5. Order changed to "Entregado" (Notify client)
            if ($newStatus === 'Entregado' && $oldStatus !== 'Entregado') {
                $this->notificationService->notifyClientOrderDelivered($order);
            }

            // 6. Trigger Baneco QR generation when status transitions to "Pendiente"
            if ($newStatus === 'Pendiente' && $oldStatus !== 'Pendiente' && $oldStatus !== null) {
                $this->dispatchBanecoQR($order);
            }
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
            'amount' => round((float) $order->total, 2),
            'description' => "Pago del pedido #{$order->id}",
            'dueDate' => now()->addDays((int) config('baneco.qr_expiration_days', 1))->format('Y-m-d'),
            'singleUse' => true,
            'modifyAmount' => false
        ];

        \App\Baneco\Jobs\GenerateQRJob::dispatchSync($qrData);
    }
}
