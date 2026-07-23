<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductVariant;
use App\Models\Recipe;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecipeRepository implements RecipeRepositoryInterface
{
    /**
     * Get all variants with recipes.
     *
     * @return Collection<int, ProductVariant>
     */
    public function all(bool $onlyActive = false): Collection
    {
        $query = ProductVariant::with(['product', 'recipes.supply']);

        if ($onlyActive) {
            $query->where('is_active', true)
                ->whereHas('product', static function ($q): void {
                    $q->where('is_active', true);
                });
        }

        return $query->get();
    }

    /**
     * Get paginated variants with recipes.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator
    {
        $query = ProductVariant::with(['product', 'recipes.supply']);

        if ($onlyActive) {
            $query->where('is_active', true)
                ->whereHas('product', static function ($q): void {
                    $q->where('is_active', true);
                });
        }

        if ($search !== null && $search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', static function ($pQ) use ($search): void {
                        $pQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Return sorted by parent product name, then variant name
        // Laravel paginate doesn't easily sort by eager loaded relations, but we can order by name
        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find a variant with its recipe items.
     */
    public function findVariantWithRecipe(int $variantId): ?ProductVariant
    {
        return ProductVariant::with(['product', 'recipes.supply'])->find($variantId);
    }

    /**
     * Sync recipe items for a variant.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function syncRecipe(ProductVariant $variant, array $items): void
    {
        DB::transaction(static function () use ($variant, $items): void {
            // Delete old recipe items
            $variant->recipes()->delete();

            // Insert new recipe items
            foreach ($items as $item) {
                Recipe::create([
                    'product_variant_id' => $variant->id,
                    'supply_id' => $item['supply_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'observation' => $item['observation'] ?? null,
                ]);
            }
        });
    }
}
