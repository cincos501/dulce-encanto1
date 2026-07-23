<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductVariantRepository implements ProductVariantRepositoryInterface
{
    /**
     * Get all product variants.
     *
     * @return Collection<int, ProductVariant>
     */
    public function all(bool $onlyActive = false): Collection
    {
        $query = ProductVariant::with('product');
        if ($onlyActive) {
            $query->where('is_active', true)
                ->whereHas('product', static function ($pQ): void {
                    $pQ->where('is_active', true);
                });
        }
        return $query->orderBy('name')->get();
    }

    /**
     * Get variants by product ID.
     *
     * @return Collection<int, ProductVariant>
     */
    public function getByProductId(int $productId, bool $onlyActive = false): Collection
    {
        $query = ProductVariant::with('product')
            ->where('product_id', $productId);

        if ($onlyActive) {
            $query->where('is_active', true)
                ->whereHas('product', static function ($pQ): void {
                    $pQ->where('is_active', true);
                });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get paginated and filtered variants.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?int $productId = null, bool $onlyActive = false): LengthAwarePaginator
    {
        $query = ProductVariant::with('product');

        if ($productId !== null) {
            $query->where('product_id', $productId);
        }

        if ($onlyActive) {
            $query->where('is_active', true)
                ->whereHas('product', static function ($pQ): void {
                    $pQ->where('is_active', true);
                });
        }

        if ($search !== null && $search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', static function ($pQuery) use ($search): void {
                        $pQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find a variant by ID.
     */
    public function findById(int $id): ?ProductVariant
    {
        return ProductVariant::with('product')->find($id);
    }

    /**
     * Create a new variant.
     */
    public function create(array $data): ProductVariant
    {
        return ProductVariant::create($data);
    }

    /**
     * Update an existing variant.
     */
    public function update(ProductVariant $variant, array $data): ProductVariant
    {
        $variant->update($data);

        return $variant;
    }

    /**
     * Delete a variant.
     */
    public function delete(ProductVariant $variant): bool
    {
        return (bool) $variant->delete();
    }
}
