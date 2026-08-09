<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Baneco\Events\QRPaid;
use App\Models\Order;
use App\Models\Customer;
use App\Services\PaymentService;
use App\Services\ChatwootService;
use Illuminate\Support\Facades\Log;

class HandleQRPaid
{
    public function __construct(
        protected PaymentService $paymentService,
        protected ChatwootService $chatwootService,
        protected \App\Services\OrderNotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(QRPaid $event): void
    {
        Log::channel('baneco')->info("HandleQRPaid: Processing payment confirmation event for QR: {$event->qrId}");

        // 1. Confirm the payment record in DB
        $payment = $this->paymentService->confirmPayment(
            $event->qrId,
            $event->paymentDate,
            $event->paymentTime,
            $event->amount
        );

        $order = Order::find((int) $event->transactionId);
        if (!$order) {
            Log::channel('baneco')->error("HandleQRPaid: Order #{$event->transactionId} not found.");
            return;
        }

        // Ensure order status is updated to Confirmado
        if ($order->status !== 'Confirmado') {
            $order->status = 'Confirmado';
            $order->save();
            Log::channel('baneco')->info("HandleQRPaid: Updated order #{$order->id} status to Confirmado.");
        }

        $customer = $order->customer;
        if (!$customer) {
            Log::channel('baneco')->error("HandleQRPaid: Order #{$order->id} has no customer associated.");
            return;
        }

        // 2. Notify the customer via Chatwoot
        if (!empty($customer->chatwoot_conversation_id)) {
            $text = "¡Pago recibido con éxito! 😊🍰\n\n" .
                    "Tu pedido #{$order->id} ha sido confirmado.\n\n" .
                    "Próximamente iniciaremos la producción de tus productos. Te mantendremos informado de cualquier actualización.";

            try {
                $this->chatwootService->sendMessage((int) $customer->chatwoot_conversation_id, $text);
                Log::channel('baneco')->info("HandleQRPaid: Sent payment confirmation WhatsApp alert to customer conversation #{$customer->chatwoot_conversation_id}");
            } catch (\Throwable $e) {
                Log::channel('baneco')->error("HandleQRPaid: Failed to send payment confirmation WhatsApp alert: " . $e->getMessage());
            }
        }

        // 3. Notify the administrator dynamically based on role
        $this->notificationService->notifyAdminPaymentReceived($order, (float) $event->amount);
    }
}
