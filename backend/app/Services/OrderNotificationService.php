<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderNotificationService
{
    public function __construct(
        protected ChatwootService $chatwootService
    ) {}

    /**
     * Notify the customer that their order is ready for delivery/pickup.
     */
    public function notifyStatusReady(Order $order): void
    {
        try {
            $customer = $order->customer;
            if (!$customer) {
                Log::warning('OrderNotificationService: Order has no associated customer.', ['order_id' => $order->id]);
                return;
            }

            $conversationId = $customer->chatwoot_conversation_id;
            if (!$conversationId) {
                Log::info('OrderNotificationService: Customer has no chatwoot_conversation_id, skipping notification.', [
                    'order_id' => $order->id,
                    'customer_id' => $customer->id
                ]);
                return;
            }

            $text = "Hola {$customer->full_name} 👋\n\n" .
                    "Tu pedido #{$order->id} ya está listo.\n\n" .
                    "Puedes pasar a recogerlo en Dulce Encanto o esperar tu entrega.\n\n" .
                    "¡Gracias por preferirnos! 🍰";

            $this->chatwootService->sendMessage((int) $conversationId, $text);

            Log::info('OrderNotificationService: WhatsApp status ready notification sent successfully.', [
                'order_id' => $order->id,
                'conversation_id' => $conversationId
            ]);
        } catch (\Throwable $e) {
            Log::error('OrderNotificationService: Failed to send status notification.', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
