<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyResource extends JsonResource
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
            'name' => $this->name,
            'unit' => $this->unit,
            'stock' => (float) $this->stock,
            'minimum_stock' => (float) $this->minimum_stock,
            'average_cost' => (float) $this->average_cost,
            'is_active' => (bool) $this->is_active,
            'suppliers' => $this->relationLoaded('suppliers') ? $this->suppliers->map(static fn($s) => [
                'id' => $s->id,
                'business_name' => $s->business_name,
                'purchase_price' => (float) $s->pivot->purchase_price,
            ]) : [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
