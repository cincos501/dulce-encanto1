<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\StoreProductionDTO;
use App\Models\ProductVariant;
use App\Models\ProductionBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionService
{
    /**
     * Record a manual production batch for a READY_STOCK variant.
     * Computes raw supplies from recipes, validates stocks, decrements supplies,
     * and increments variant stock.
     */
    public function recordProduction(StoreProductionDTO $dto): ProductionBatch
    {
        $variant = ProductVariant::findOrFail($dto->productVariantId);

        // Validate that variant is READY_STOCK
        if ($variant->sale_type !== 'READY_STOCK') {
            throw ValidationException::withMessages([
                'product_variant_id' => ["Únicamente se puede registrar producción manual de variantes clasificadas como READY_STOCK."]
            ]);
        }

        // 1. Calculate required quantities of supplies based on recipes
        $requiredSupplies = []; // [supply_id => ['required' => float, 'supply' => Supply, 'unit' => string]]
        foreach ($variant->recipes as $recipeItem) {
            $supply = $recipeItem->supply;
            if ($supply === null) {
                continue;
            }

            $neededQty = (float) $recipeItem->quantity * $dto->quantity;
            $requiredSupplies[$supply->id] = [
                'required' => $neededQty,
                'supply' => $supply,
                'unit' => $recipeItem->unit,
            ];
        }

        // 2. Validate supply stock availability
        $validationErrors = [];
        foreach ($requiredSupplies as $supplyId => $data) {
            $supply = $data['supply'];
            $required = $data['required'];
            $available = (float) $supply->stock;

            if ($available < $required) {
                $validationErrors[] = "Stock de insumo insuficiente para '{$supply->name}'. Disponible: " . number_format($available, 4) . " {$data['unit']}, Requerido: " . number_format($required, 4) . " {$data['unit']}.";
            }
        }

        if (!empty($validationErrors)) {
            throw ValidationException::withMessages([
                'quantity' => $validationErrors
            ]);
        }

        // 3. Execute deduction and increment in database transaction
        return DB::transaction(function () use ($variant, $dto, $requiredSupplies): ProductionBatch {
            // Deduct supplies
            foreach ($requiredSupplies as $supplyId => $data) {
                $supply = $data['supply'];
                $supply->stock -= $data['required'];
                $supply->save();

                // Fire event if supply is low
                if ($supply->stock <= $supply->minimum_stock) {
                    event(new \App\Events\SupplyStockLow($supply));
                }
            }

            // Increment variant stock
            $variant->stock += $dto->quantity;
            $variant->save();

            // Create batch log
            return ProductionBatch::create([
                'product_variant_id' => $variant->id,
                'quantity' => $dto->quantity,
                'notes' => $dto->notes,
                'production_date' => now(),
            ]);
        });
    }

    /**
     * Get paginated production history.
     */
    public function getProductionHistory(int $perPage = 15)
    {
        return ProductionBatch::with(['productVariant.product'])
            ->orderBy('production_date', 'desc')
            ->paginate($perPage);
    }
}
