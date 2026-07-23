<?php

declare(strict_types=1);

namespace App\AI\Tools\Orders;

use App\AI\Contracts\ToolInterface;
use App\AI\Orders\OrderDraftManager;

class GetOrderDraftSummaryTool implements ToolInterface
{
    public function __construct(
        protected OrderDraftManager $draftManager
    ) {}

    public function getName(): string
    {
        return 'get_order_draft_summary';
    }

    public function getDescription(): string
    {
        return 'Obtener un resumen estructurado y detallado del pedido temporal (borrador) actual del cliente, incluyendo lista de productos, extras, cantidades, subtotales y total.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $phone = $context['phone'] ?? null;
        if (!$phone) {
            return "Error: Contexto de teléfono de cliente no disponible.";
        }

        $draft = $this->draftManager->getDraft($phone);

        if (empty($draft->items)) {
            return "El pedido temporal actual está vacío.";
        }

        $result = "Resumen del Pedido Temporal:\n";
        foreach ($draft->items as $item) {
            $result .= "- {$item->productName} ({$item->variantName}) x{$item->quantity} | Precio unitario: Bs. {$item->unitPrice}\n";
            if (!empty($item->extras)) {
                $extrasStr = implode(', ', array_map(fn($e) => "{$e['name']} (+Bs. {$e['price']})", $item->extras));
                $result .= "  * Adicionales: {$extrasStr}\n";
            }
            $result .= "  * Subtotal del producto: Bs. {$item->subtotal}\n";
        }
        $result .= "\n**Total del Pedido: Bs. {$draft->total}**";

        return $result;
    }
}
