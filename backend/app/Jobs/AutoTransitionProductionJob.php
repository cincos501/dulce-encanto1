<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoTransitionProductionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(OrderService $orderService): void
    {
        Log::info("AutoTransitionProductionJob: Checking orders for automated production transitions...");

        // Fetch orders that are in production phases but not yet Listo or Cancelled/Delivered
        $orders = Order::whereIn('status', ['Confirmado', 'En preparación'])
            ->where('production_stage', '!=', 'Listo')
            ->get();

        foreach ($orders as $order) {
            if (!$order->delivery_date) {
                continue;
            }

            // Determine confirmation start time Tc
            $payment = $order->payments()->where('status', 'Completado')->first();
            $tc = $payment?->payment_date ?: $order->created_at;
            $td = $order->delivery_date;

            $now = now();
            
            // Calculate total window and elapsed time in seconds
            $W = $td->timestamp - $tc->timestamp;
            $elapsed = $now->timestamp - $tc->timestamp;

            if ($W <= 0) {
                // If delivery date is in the past or immediately due, default to Listo
                $targetStage = 'Listo';
            } else {
                $elapsed = max(0, $elapsed);

                if ($W >= 20 * 3600) {
                    // Fixed hours offset window
                    if ($elapsed >= $W - 2 * 3600) {
                        $targetStage = 'Listo';
                    } elseif ($elapsed >= $W - 3 * 3600) {
                        $targetStage = 'Empaque';
                    } elseif ($elapsed >= $W - 6 * 3600) {
                        $targetStage = 'Decoración';
                    } elseif ($elapsed >= $W - 12 * 3600) {
                        $targetStage = 'Horneado';
                    } elseif ($elapsed >= $W - 18 * 3600) {
                        $targetStage = 'Preparación';
                    } else {
                        $targetStage = 'Programado';
                    }
                } else {
                    // Compressed proportional window
                    if ($elapsed >= 0.9 * $W) {
                        $targetStage = 'Listo';
                    } elseif ($elapsed >= 0.8 * $W) {
                        $targetStage = 'Empaque';
                    } elseif ($elapsed >= 0.6 * $W) {
                        $targetStage = 'Decoración';
                    } elseif ($elapsed >= 0.3 * $W) {
                        $targetStage = 'Horneado';
                    } elseif ($elapsed >= 0.1 * $W) {
                        $targetStage = 'Preparación';
                    } else {
                        $targetStage = 'Programado';
                    }
                }
            }

            if ($targetStage !== $order->production_stage) {
                Log::info("AutoTransitionProductionJob: Order #{$order->id} transitioning from stage '{$order->production_stage}' to '{$targetStage}'");

                try {
                    // 1. Transition the main status
                    if ($targetStage === 'Listo') {
                        $orderService->updateStatus((int) $order->id, 'Listo');
                    } elseif ($targetStage !== 'Programado' && $order->status === 'Confirmado') {
                        // First change to production: transitions status from Confirmado to En preparación
                        // This will trigger stock deduction and recipe checks
                        $orderService->updateStatus((int) $order->id, 'En preparación');
                    }

                    // 2. Update the internal production stage
                    $order->production_stage = $targetStage;
                    $order->save();

                    Log::info("AutoTransitionProductionJob: Order #{$order->id} successfully updated to stage '{$targetStage}' and status '{$order->status}'.");

                } catch (\Illuminate\Validation\ValidationException $e) {
                    Log::warning("AutoTransitionProductionJob: Cannot transition Order #{$order->id} due to validation issue (e.g. insufficient stock): " . $e->getMessage());
                } catch (\Throwable $e) {
                    Log::error("AutoTransitionProductionJob: Exception during Order #{$order->id} transition: " . $e->getMessage());
                }
            }
        }
    }
}
