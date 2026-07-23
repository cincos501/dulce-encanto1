<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->productVariant?->product?->name ?? 'Producto Inexistente',
            'presentation_name' => $this->productVariant?->name ?? 'Presentación Inexistente',
            'sku' => $this->productVariant?->sku ?? '',
            'quantity' => (int) $this->quantity,
            'price' => (float) $this->price,
            'total' => (float) $this->price * (int) $this->quantity,
        ];
    }
}
