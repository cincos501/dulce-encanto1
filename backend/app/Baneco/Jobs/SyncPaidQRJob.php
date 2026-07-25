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

class SyncPaidQRJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $date // Format yyyyMMdd
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BanecoService $banecoService): void
    {
        Log::channel('baneco')->info("SyncPaidQRJob: Reconciling paid QRs for date: {$this->date}");

        $response = $banecoService->paidQR($this->date);

        if ($response->responseCode === 0) {
            Log::channel('baneco')->info("SyncPaidQRJob: Fetched " . count($response->paymentList) . " payments for reconciliation.");

            foreach ($response->paymentList as $payment) {
                $orderId = (int) $payment->transactionId;
                $order = Order::find($orderId);

                if ($order && $order->status === 'Pendiente') {
                    Log::channel('baneco')->info("SyncPaidQRJob: Reconciling payment for order #{$orderId}.", [
                        'qrId' => $payment->qrId,
                        'amount' => $payment->amount
                    ]);

                    DB::transaction(function () use ($order) {
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
            }
        } else {
            Log::channel('baneco')->error("SyncPaidQRJob: Failed to retrieve paid QRs for date: {$this->date}", [
                'message' => $response->message
            ]);
        }
    }
}
