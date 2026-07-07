<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\StorageServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $storage = app(StorageServiceInterface::class);

        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'image_url' => $storage->url($this->image_url),
            'is_primary' => (bool) $this->is_primary,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
