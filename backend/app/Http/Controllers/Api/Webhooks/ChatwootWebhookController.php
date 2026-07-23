<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\ChatwootWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChatwootWebhookController extends Controller
{
    public function __construct(
        protected ChatwootWebhookService $webhookService
    ) {}

    /**
     * Handle incoming webhooks from Chatwoot.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Validate that the request is JSON
        if (!$request->isJson()) {
            Log::warning('Rejected non-JSON Chatwoot webhook request');
            return response()->json([
                'success' => false,
                'error' => 'Content-Type must be application/json'
            ], 400);
        }

        $payload = $request->all();

        // 2. Log full payload for development purposes
        Log::debug('Full Chatwoot webhook payload received', [
            'payload' => $payload
        ]);

        // 3. Delegate to the service
        $result = $this->webhookService->processPayload($payload);

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ], $result['status']);
    }
}
