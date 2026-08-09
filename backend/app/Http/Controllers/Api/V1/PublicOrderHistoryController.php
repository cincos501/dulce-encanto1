<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OrderHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PublicOrderHistoryController extends Controller
{
    public function __construct(
        protected OrderHistoryService $historyService
    ) {}

    /**
     * Get the customer order history by phone token.
     */
    public function show(string $phoneToken): JsonResponse
    {
        Log::info("PublicOrderHistoryController: Querying order history for token prefix: " . substr($phoneToken, 0, 8));

        $history = $this->historyService->getCustomerHistory($phoneToken);

        if (empty($history)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => null,
                    'orders' => []
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
}
