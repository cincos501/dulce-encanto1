<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => (string) $this->name,
            'stock' => (float) $this->stock,
            'unit' => (string) $this->unit,
            'minimum_stock' => (float) $this->minimum_stock,
            'status' => (string) $this->status,
        ];
    }
}
