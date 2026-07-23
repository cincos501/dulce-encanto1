<?php

declare(strict_types=1);

namespace App\AI\Tools\Orders;

use App\AI\Contracts\ToolInterface;
use App\AI\Orders\OrderDraftManager;

class RemoveFromOrderDraftTool implements ToolInterface
{
    public function __construct(
        protected OrderDraftManager $draftManager
    ) {}

    public function getName(): string
    {
        return 'remove_from_order_draft';
    }

    public function getDescription(): string
    {
        return 'Eliminar un producto (especificando su variant_id) del borrador del pedido temporal.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'variant_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la presentación/variante del producto a eliminar del pedido.'
                ]
            ],
            'required' => ['variant_id']
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $phone = $context['phone'] ?? null;
        if (!$phone) {
            return "Error: Contexto de teléfono de cliente no disponible.";
        }

        $variantId = (int) $arguments['variant_id'];
        $draft = $this->draftManager->removeItem($phone, $variantId);

        return "Producto eliminado del pedido temporal. El subtotal del pedido ahora es Bs. {$draft->subtotal}.";
    }
}
