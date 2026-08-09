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

    /**
     * Notify the customer that their order has been cancelled.
     */
    public function notifyStatusCancelled(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer || empty($customer->chatwoot_conversation_id)) {
            return;
        }

        $text = "Hola {$customer->full_name} 👋\n\n" .
                "Te informamos que tu pedido #{$order->id} ha sido cancelado. 🍰";

        $this->sendNotificationByPhone($customer->phone, $text, 'Client Cancelled');
    }

    /**
     * Notify the administrator or operations manager that an order is ready for delivery/pickup.
     */
    public function notifyAdminOrderReady(Order $order): void
    {
        $customer = $order->customer;

        $text = "📢 PEDIDO LISTO PARA DESPACHO/RETIRO\n\n" .
                "• Número de pedido: #{$order->id}\n" .
                "• Cliente: " . ($customer?->full_name ?? 'Cliente') . "\n" .
                "• Teléfono: " . ($customer?->phone ?? 'N/A') . "\n" .
                "• Fecha de entrega: " . ($order->delivery_date ? $order->delivery_date->format('Y-m-d H:i') : 'No especificada');

        // Operations manager is responsible for delivery
        $this->notifyRole('Encargado de Operaciones y Suministros', $text, 'Admin Order Ready');
    }

    /**
     * Notify the repostero (pastry chef) that a paid order has been confirmed.
     */
    public function notifyReposteroOrderConfirmed(Order $order): void
    {
        $customer = $order->customer;

        $text = "🍰 NUEVO PEDIDO CONFIRMADO (POR PREPARAR)\n\n" .
                "• Número de pedido: #{$order->id}\n" .
                "• Cliente: " . ($customer?->full_name ?? 'Cliente') . "\n" .
                "• Fecha de entrega: " . ($order->delivery_date ? $order->delivery_date->format('Y-m-d H:i') : 'No especificada') . "\n" .
                "• Por favor, revisa los detalles en el panel de administración.";

        $this->notifyRole('Repostero', $text, 'Repostero Order Confirmed');
    }

    /**
     * Notify client that their order has been confirmed.
     */
    public function notifyClientOrderConfirmed(Order $order): void
    {
        $customer = $order->customer;

        if (!$customer || empty($customer->chatwoot_conversation_id)) {
            Log::info("Client has no active Chatwoot conversation");
            return;
        }

        Log::info("OrderNotificationService:\nSending client order confirmed notification\n\ncustomer_id: {$customer->id}\nphone: {$customer->phone}\nconversation_id: {$customer->chatwoot_conversation_id}");

        $text = "Hola {$customer->full_name} 👋\n\n" .
                "Tu pedido #{$order->id} ha sido confirmado correctamente. 🍰\n\n" .
                "Nuestro equipo empezará la preparación de tu pedido.\n\n" .
                "¡Gracias por elegir Dulce Encanto!";

        try {
            $this->chatwootService->sendMessage((int) $customer->chatwoot_conversation_id, $text);
        } catch (\Throwable $e) {
            Log::error("OrderNotificationService: Failed to send Client Order Confirmed notification.", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify the repostero (pastry chef) that an order status changed to "En preparación".
     */
    public function notifyReposteroOrderInPreparation(Order $order): void
    {
        $customer = $order->customer;

        $text = "👩‍🍳 PEDIDO EN PREPARACIÓN\n\n" .
                "• Número de pedido: #{$order->id}\n" .
                "• Cliente: " . ($customer?->full_name ?? 'Cliente') . "\n" .
                "• El repostero ha iniciado la elaboración del pastel.";

        $this->notifyRole('Repostero', $text, 'Repostero Order In Preparation');
    }

    /**
     * Notify the administrator that a payment was received.
     */
    public function notifyAdminPaymentReceived(Order $order, float $amount): void
    {
        $customer = $order->customer;
        $text = "📢 NUEVO PAGO RECIBIDO (PEDIDO CONFIRMADO)\n\n" .
                "• Número de pedido: #{$order->id}\n" .
                "• Cliente: " . ($customer?->full_name ?? 'Cliente') . "\n" .
                "• Teléfono: " . ($customer?->phone ?? 'N/A') . "\n" .
                "• Monto pagado: Bs. " . number_format($amount, 2) . "\n" .
                "• Monto restante: Bs. 0.00";

        $this->notifyRole('Administrador', $text, 'Admin Payment Received');
    }

    /**
     * Notify client that their order is in preparation stage.
     */
    public function notifyClientOrderInPreparation(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer || empty($customer->chatwoot_conversation_id)) {
            return;
        }

        $text = "Hola {$customer->full_name} 👋\n\n" .
                "Te informamos que tu pedido #{$order->id} ya se encuentra en preparación. 👩‍🍳🍰\n\n" .
                "¡Nuestros reposteros están poniendo manos a la obra!";

        $this->sendNotificationByPhone($customer->phone, $text, 'Client Order In Preparation');
    }

    /**
     * Notify client that their order has been delivered.
     */
    public function notifyClientOrderDelivered(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer || empty($customer->chatwoot_conversation_id)) {
            return;
        }

        $text = "¡Hola {$customer->full_name}! 👋\n\n" .
                "Tu pedido #{$order->id} ha sido entregado. 🎉\n\n" .
                "Esperamos que lo disfrutes muchísimo. ¡Que tengas un dulce día! 🍰✨";

        $this->sendNotificationByPhone($customer->phone, $text, 'Client Order Delivered');
    }

    /**
     * Notify operations when a supply stock level drops below minimum.
     */
    public function notifyLowSupply(\App\Models\Supply $supply): void
    {
        $text = "⚠️ ALERTA DE STOCK MÍNIMO\n\n" .
                "• Insumo: '{$supply->name}'\n" .
                "• Stock Actual: " . number_format((float) $supply->stock, 4) . "\n" .
                "• Stock Mínimo: " . number_format((float) $supply->minimum_stock, 4) . "\n" .
                "• Por favor, gestione el reabastecimiento con el proveedor.";

        $this->notifyRole('Encargado de Operaciones y Suministros', $text, 'Supply Stock Low');
    }

    /**
     * Notify operations and commercial manager when a READY_STOCK variant is out of stock.
     */
    public function notifyReadyStockOutOfStock(ProductVariant $variant): void
    {
        $productName = $variant->product?->name ?? 'Producto';
        $text = "🚨 ALERTA: READY_STOCK AGOTADO\n\n" .
                "• Presentación: '{$productName} ({$variant->name})'\n" .
                "• SKU: {$variant->sku}\n" .
                "• Stock: 0 unidades\n" .
                "• Se requiere registrar un nuevo lote de producción manual para reactivar la venta de entrega inmediata.";

        $this->notifyRole('Encargado de Operaciones y Suministros', $text, 'Variant Out of Stock (Ops)');
        $this->notifyRole('Encargado Comercial', $text, 'Variant Out of Stock (Com)');
    }

    public function notifyRole(string $roleName, string $text, string $label): void
    {
        try {
            if (!\Spatie\Permission\Models\Role::where('name', $roleName)->exists()) {
                Log::info("OrderNotificationService: Role '{$roleName}' does not exist, skipping role notification.");
                return;
            }

            $users = \App\Models\User::role($roleName)
                ->where('is_active', true)
                ->whereNotNull('phone')
                ->get();

            Log::info("OrderNotificationService: Sending {$label} notification to role '{$roleName}'. Users count: " . $users->count());

            foreach ($users as $user) {
                $this->sendNotificationByPhone($user->phone, $text, "{$label} ({$user->full_name})");
            }
        } catch (\Throwable $e) {
            Log::error("OrderNotificationService: Failed to send role notification for {$roleName}.", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper to send notification to a specific phone number via active Chatwoot conversation.
     */
    protected function sendNotificationByPhone(string $phone, string $text, string $label): void
    {
        try {
            $normalizedPhone = \App\Support\PhoneHelper::normalize($phone);
            $customer = \App\Models\Customer::where('phone', $normalizedPhone)->first();

            $conversationId = $customer?->chatwoot_conversation_id;

            Log::info("OrderNotificationService: Attempting {$label} notification.", [
                'phone' => $normalizedPhone,
                'conversation_id' => $conversationId
            ]);

            if ($conversationId) {
                $this->chatwootService->sendMessage((int) $conversationId, $text);
                Log::info("OrderNotificationService: {$label} notification sent successfully via Chatwoot.");
            } else {
                Log::info("OrderNotificationService: Skipping Chatwoot notification (no active conversation). Notification content:\n{$text}");
            }
        } catch (\Throwable $e) {
            Log::error("OrderNotificationService: Failed to send {$label} notification.", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
