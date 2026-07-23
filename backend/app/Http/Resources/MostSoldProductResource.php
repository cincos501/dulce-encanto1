<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MostSoldProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'product_name' => (string) $this->product_name,
            'variant_name' => (string) $this->variant_name,
            'quantity_sold' => (float) $this->quantity_sold,
            'total_generated' => (float) $this->total_generated,
        ];
    }
}
