<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pendingPayment = $this->payments()->where('status', 'Pendiente')->first();

        return [
            'id' => $this->id,
            'status' => $this->status,
            'payment_status' => $this->payment_status ?? 'Pendiente',
            'production_stage' => $this->production_stage ?? 'Programado',
            'qr_id' => $pendingPayment?->transaction_code,
            'total' => (float) $this->total,
            'delivery_date' => $this->delivery_date?->toIso8601String(),
            'delivery_type' => $this->delivery_type ?? 'RECOJO_TIENDA',
            'delivery_address' => $this->delivery_address,
            'delivery_notes' => $this->delivery_notes,
            'customer' => [
                'id' => $this->customer?->id,
                'full_name' => $this->customer?->full_name ?? 'Cliente Anónimo',
                'email' => $this->customer?->email,
                'phone' => $this->customer?->phone,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
