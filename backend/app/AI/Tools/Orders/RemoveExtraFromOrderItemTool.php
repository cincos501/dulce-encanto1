<?php

declare(strict_types=1);

namespace App\AI\Tools\Orders;

use App\AI\Contracts\ToolInterface;
use App\AI\Orders\OrderDraftManager;

class RemoveExtraFromOrderItemTool implements ToolInterface
{
    public function __construct(
        protected OrderDraftManager $draftManager
    ) {}

    public function getName(): string
    {
        return 'remove_extra_from_order_item';
    }

    public function getDescription(): string
    {
        return 'Eliminar un adicional (extra) de un producto específico en el pedido (especificando variant_id y extra_id).';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'variant_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la presentación/variante del producto del cual eliminar el extra.'
                ],
                'extra_id' => [
                    'type' => 'integer',
                    'description' => 'ID del adicional (extra) a eliminar.'
                ]
            ],
            'required' => ['variant_id', 'extra_id']
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $phone = $context['phone'] ?? null;
        if (!$phone) {
            return "Error: Contexto de teléfono de cliente no disponible.";
        }

        $variantId = (int) $arguments['variant_id'];
        $extraId = (int) $arguments['extra_id'];

        $draft = $this->draftManager->removeExtra($phone, $variantId, $extraId);

        return "Adicional eliminado del producto en el pedido temporal. El subtotal ahora es Bs. {$draft->subtotal}.";
    }
}
