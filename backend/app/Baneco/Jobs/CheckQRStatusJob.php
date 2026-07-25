<?php

declare(strict_types=1);

namespace App\Baneco\Jobs;

use App\Baneco\Services\BanecoService;
use App\Baneco\Events\QRPaid;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckQRStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $qrId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BanecoService $banecoService): void
    {
        Log::channel('baneco')->info("CheckQRStatusJob: Querying status for QR: {$this->qrId}");

        $response = $banecoService->statusQR($this->qrId);

        if ($response->responseCode === 0) {
            // statusQRCode (0: active, 1: paid, 9: cancelled)
            if ($response->statusQRCode === 1 && $response->paymentQR) {
                $payment = $response->paymentQR;

                Log::channel('baneco')->info("CheckQRStatusJob: QR: {$this->qrId} has been PAID.", [
                    'transactionId' => $payment->transactionId,
                    'amount' => $payment->amount
                ]);

                // Find order by transactionId (which is the order ID or custom string)
                $orderId = (int) $payment->transactionId;
                $order = Order::find($orderId);
                
                if ($order && $order->status === 'Pendiente') {
                    DB::transaction(function () use ($order, $payment) {
                        // Update order status to Confirmado (Pagado state)
                        $order->status = 'Confirmado';
                        $order->save();
                    });

                    event(new QRPaid(
                        qrId: $payment->qrId,
                        transactionId: $payment->transactionId,
                        paymentDate: $payment->paymentDate,
                        paymentTime: $payment->paymentTime,
                        amount: $payment->amount
                    ));
                }
            } else {
                Log::channel('baneco')->info("CheckQRStatusJob: QR: {$this->qrId} status is {$response->statusQRCode} (not paid).");
            }
        } else {
            Log::channel('baneco')->error("CheckQRStatusJob: Failed to check status for QR: {$this->qrId}", [
                'message' => $response->message
            ]);
        }
    }
}
