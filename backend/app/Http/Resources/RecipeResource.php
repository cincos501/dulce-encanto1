<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, // variant id
            'name' => $this->name, // variant name
            'sku' => $this->sku,
            'is_active' => (bool) $this->is_active,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
            ],
            'items' => $this->relationLoaded('recipes') ? $this->recipes->map(static fn($r) => [
                'id' => $r->id,
                'supply_id' => $r->supply_id,
                'supply_name' => $r->supply?->name ?? 'Insumo Inexistente',
                'quantity' => (float) $r->quantity,
                'unit' => $r->unit,
                'observation' => $r->observation,
            ]) : [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
