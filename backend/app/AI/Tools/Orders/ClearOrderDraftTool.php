<?php

declare(strict_types=1);

namespace App\AI\Tools\Orders;

use App\AI\Contracts\ToolInterface;
use App\AI\Orders\OrderDraftManager;

class ClearOrderDraftTool implements ToolInterface
{
    public function __construct(
        protected OrderDraftManager $draftManager
    ) {}

    public function getName(): string
    {
        return 'clear_order_draft';
    }

    public function getDescription(): string
    {
        return 'Vaciar o cancelar completamente el pedido temporal actual del cliente.';
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

        $this->draftManager->clearDraft($phone);

        return "El pedido temporal ha sido vaciado completamente.";
    }
}
