<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PublicPaymentController extends Controller
{
    /**
     * Get payment details and QR code for public payment screen.
     */
    public function show(string $qrId): JsonResponse
    {
        Log::channel('baneco')->info("PublicPaymentController: Fetching payment details for QR ID: {$qrId}");

        $payment = Payment::with(['order.customer', 'order.items.productVariant.product'])->where('transaction_code', $qrId)->first();

        if (!$payment) {
            Log::channel('baneco')->warning("PublicPaymentController: Payment with QR ID {$qrId} not found.");
            return response()->json([
                'success' => false,
                'message' => 'El código de pago solicitado no existe o ha expirado.',
            ], 404);
        }

        $order = $payment->order;

        if ($payment->status === 'Pendiente') {
            try {
                $banecoService = app(\App\Baneco\Services\BanecoService::class);
                $response = $banecoService->statusQR($qrId);
                
                if ($response->responseCode === 0 && $response->statusQRCode === 1 && $response->paymentQR) {
                    $paymentQR = $response->paymentQR;
                    
                    \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
                        $order = $payment->order;
                        if ($order->status === 'Pendiente') {
                            $order->status = 'Confirmado';
                            $order->save();
                        }
                        
                        $payment->status = 'Completado';
                        $payment->payment_date = now();
                        $payment->save();
                    });

                    // Fire the paid event to notify the user/admin
                    event(new \App\Baneco\Events\QRPaid(
                        qrId: $paymentQR->qrId,
                        transactionId: $paymentQR->transactionId,
                        paymentDate: $paymentQR->paymentDate,
                        paymentTime: $paymentQR->paymentTime,
                        amount: (float) $paymentQR->amount
                    ));

                    // Refresh in-memory model state
                    $payment->refresh();
                }
            } catch (\Throwable $e) {
                Log::channel('baneco')->error('PublicPaymentController: Automatic status verification failed.', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'qr_id' => $payment->transaction_code,
                'order_id' => $order->id,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'payment_date' => $payment->payment_date?->toIso8601String(),
                'qr_image_url' => asset("storage/qrs/{$payment->transaction_code}.png"),
                'customer' => [
                    'name' => $order->customer?->full_name,
                    'phone' => $order->customer?->phone,
                ],
                'items' => array_map(function ($item) {
                    return [
                        'quantity' => $item->quantity,
                        'price' => (float) $item->price,
                        'product_name' => $item->productVariant?->product?->name ?? 'Producto',
                        'variant_name' => $item->productVariant?->name ?? 'Presentación',
                    ];
                }, $order->items->all()),
            ]
        ]);
    }
}
