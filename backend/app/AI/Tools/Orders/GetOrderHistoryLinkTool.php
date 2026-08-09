<?php

declare(strict_types=1);

namespace App\AI\Tools\Orders;

use App\AI\Contracts\ToolInterface;
use App\Services\OrderHistoryService;
use Illuminate\Support\Facades\Log;

class GetOrderHistoryLinkTool implements ToolInterface
{
    public function __construct(
        protected OrderHistoryService $historyService
    ) {}

    public function getName(): string
    {
        return 'get_order_history_link';
    }

    public function getDescription(): string
    {
        return 'Genera y devuelve el enlace público seguro para que el cliente pueda consultar su historial de pedidos y estado de producción actual.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $phone = $context['phone'] ?? null;
        if (!$phone) {
            Log::warning("GetOrderHistoryLinkTool: Customer phone context is missing.");
            return "Error: No se pudo identificar su número de teléfono en esta conversación.";
        }

        // Generate the secure token
        $token = $this->historyService->generatePhoneToken($phone);
        
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');
        $historyLink = "{$frontendUrl}/history/{$token}";

        Log::info("GetOrderHistoryLinkTool: Generated order history link for customer phone {$phone}");

        return "SUCCESS: Aquí tiene el enlace seguro para consultar su historial de pedidos y estado de producción: {$historyLink}";
    }
}
