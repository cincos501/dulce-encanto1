<?php

declare(strict_types=1);

namespace App\AI\Tools\Orders;

use App\AI\Contracts\ToolInterface;
use App\AI\Orders\OrderDraftManager;

class UpdateOrderItemQuantityTool implements ToolInterface
{
    public function __construct(
        protected OrderDraftManager $draftManager
    ) {}

    public function getName(): string
    {
        return 'update_order_item_quantity';
    }

    public function getDescription(): string
    {
        return 'Modificar la cantidad de unidades de un producto (especificando variant_id y la nueva cantidad) en el pedido temporal.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'variant_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la presentación/variante del producto a actualizar.'
                ],
                'quantity' => [
                    'type' => 'integer',
                    'description' => 'Nueva cantidad total de unidades (debe ser mayor a 0).'
                ]
            ],
            'required' => ['variant_id', 'quantity']
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $phone = $context['phone'] ?? null;
        if (!$phone) {
            return "Error: Contexto de teléfono de cliente no disponible.";
        }

        $variantId = (int) $arguments['variant_id'];
        $quantity = (int) $arguments['quantity'];

        if ($quantity <= 0) {
            return "Error: La cantidad debe ser mayor a 0. Para eliminar el producto, use la herramienta remove_from_order_draft.";
        }

        $draft = $this->draftManager->updateQuantity($phone, $variantId, $quantity);

        return "Cantidad actualizada a {$quantity} unidad(es). El subtotal del pedido ahora es Bs. {$draft->subtotal}.";
    }
}
