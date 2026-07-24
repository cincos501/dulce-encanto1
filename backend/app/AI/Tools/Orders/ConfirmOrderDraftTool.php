<?php

declare(strict_types=1);

namespace App\AI\Tools\Orders;

use App\AI\Contracts\ToolInterface;
use App\AI\Orders\OrderDraftManager;
use App\Services\OrderService;
use App\DTO\StoreOrderDTO;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ConfirmOrderDraftTool implements ToolInterface
{
    public function __construct(
        protected OrderDraftManager $draftManager,
        protected OrderService $orderService
    ) {}

    public function getName(): string
    {
        return 'confirm_order_draft';
    }

    public function getDescription(): string
    {
        return 'Consolidar y registrar de forma definitiva el borrador de pedido de un cliente en la base de datos MySQL, vaciando el carrito temporal tras completarse.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Nombre completo del cliente para registrar el pedido.'
                ],
                'delivery_type' => [
                    'type' => 'string',
                    'enum' => ['Retiro en tienda', 'Delivery'],
                    'description' => 'Modalidad de entrega del pedido.'
                ],
                'address' => [
                    'type' => 'string',
                    'description' => 'Dirección de envío completa. Obligatoria únicamente si el tipo de entrega es Delivery.'
                ],
                'delivery_date' => [
                    'type' => 'string',
                    'description' => 'Fecha solicitada de entrega en formato AAAA-MM-DD (ej: 2026-07-25).'
                ],
                'delivery_time' => [
                    'type' => 'string',
                    'description' => 'Hora solicitada de entrega en formato HH:MM (ej: 15:30).'
                ],
                'observations' => [
                    'type' => 'string',
                    'description' => 'Observaciones o notas especiales del pedido (ej. frase escrita en el pastel).'
                ]
            ],
            'required' => ['customer_name', 'delivery_type', 'delivery_date', 'delivery_time']
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $phone = $context['phone'] ?? null;
        if (!$phone) {
            return "Error: Contexto de teléfono de cliente no disponible.";
        }

        $customerName = $arguments['customer_name'];
        $deliveryType = $arguments['delivery_type'];
        $address = $arguments['address'] ?? null;
        $deliveryDate = $arguments['delivery_date'];
        $deliveryTime = $arguments['delivery_time'];
        $observations = $arguments['observations'] ?? null;

        // 1. Validate Delivery address requirement
        if ($deliveryType === 'Delivery' && empty($address)) {
            return "Error: La dirección es requerida cuando el tipo de entrega es Delivery.";
        }

        // 2. Load draft
        $draft = $this->draftManager->getDraft($phone);
        if (empty($draft->items)) {
            return "Error: Tu borrador de pedido está vacío.";
        }

        // 3. Parse and validate delivery date and time
        try {
            $requestedDateTime = Carbon::createFromFormat('Y-m-d H:i', "{$deliveryDate} {$deliveryTime}");
        } catch (\Throwable $e) {
            return "Error: Formato de fecha u hora inválido. Asegúrese de ingresar la fecha en formato AAAA-MM-DD y la hora en formato HH:MM.";
        }

        // 4. Validate cake 24-hours advance notice requirement
        $hasTorta = false;
        foreach ($draft->items as $item) {
            if (stripos($item->productName, 'torta') !== false) {
                $hasTorta = true;
                break;
            }
            $product = Product::with('category')->find($item->productId);
            if ($product && $product->category && stripos($product->category->name, 'torta') !== false) {
                $hasTorta = true;
                break;
            }
        }

        if ($hasTorta && $requestedDateTime->lt(now()->addHours(24))) {
            return "Lo sentimos, nuestras tortas requieren mínimo 24 horas de anticipación. ¿Desea elegir otra fecha?";
        }

        // 5. Build DTO items array structure
        $items = [];
        foreach ($draft->items as $item) {
            $items[] = [
                'product_variant_id' => $item->variantId,
                'quantity' => $item->quantity,
                'extras' => array_map(fn($e) => (int) $e['id'], $item->extras)
            ];
        }

        try {
            // 6. Instantiate StoreOrderDTO and create order
            $dto = StoreOrderDTO::fromArray([
                'customer_name' => $customerName,
                'customer_phone' => $phone,
                'delivery_type' => $deliveryType,
                'address' => $address,
                'observations' => $observations,
                'delivery_date' => $deliveryDate,
                'delivery_time' => $deliveryTime,
                'items' => $items
            ]);

            $order = $this->orderService->createOrder($dto);

            // 7. Clear the draft in Redis
            $this->draftManager->clearDraft($phone);

            return "¡Perfecto! Tu pedido fue registrado correctamente.\n\nNúmero de pedido: #{$order->id}\n\nTe estaremos informando cualquier actualización.";
        } catch (ValidationException $e) {
            Log::warning('Validation error during chatbot order confirmation', ['errors' => $e->errors()]);
            $errorMessages = implode(', ', Arr::flatten($e->errors()));
            return "Error de validación: {$errorMessages}";
        } catch (\Throwable $e) {
            Log::error('Unexpected error during chatbot order confirmation', ['error' => $e->getMessage()]);
            return "Error al registrar el pedido: " . $e->getMessage();
        }
    }
}
