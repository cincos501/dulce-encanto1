<?php

declare(strict_types=1);

namespace App\Baneco\Http\Controllers;

use App\Baneco\Jobs\ProcessPaymentNotificationJob;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class BanecoWebhookController extends Controller
{
    /**
     * Webhook to receive payment confirmation from Banco Economico.
     */
    public function notifyPaymentQR(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::channel('baneco')->info('Baneco Webhook: Received payment notification.', [
            'ip' => $request->ip(),
            'payload' => $payload
        ]);

        // Validate basic payload fields required by PaymentQR DTO
        $validator = Validator::make($payload, [
            'qrId' => 'required|string',
            'transactionId' => 'required|string',
            'paymentDate' => 'required|string',
            'paymentTime' => 'required|string',
            'currency' => 'required|string',
            'amount' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            Log::channel('baneco')->warning('Baneco Webhook: Rejected malformed request.', [
                'errors' => $validator->errors()->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Malformed request'
            ], 400);
        }

        // Idempotency check: prevent processing the same qrId multiple times
        $qrId = (string) $payload['qrId'];
        $cacheKey = "baneco_payment_processed_{$qrId}";

        if (Cache::has($cacheKey)) {
            Log::channel('baneco')->info("Baneco Webhook: Payment notification for QR {$qrId} already processed (Idempotent ignore).");
            return response()->json([
                'success' => true,
                'message' => 'Notification already processed'
            ], 200);
        }

        // Cache the processed status for 24 hours
        Cache::put($cacheKey, true, now()->addHours(24));

        // Dispatch job to update MySQL order status asynchronously
        ProcessPaymentNotificationJob::dispatch($payload);

        return response()->json([
            'success' => true,
            'message' => 'Notification queued successfully'
        ], 200);
    }
}
