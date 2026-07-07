<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Get all products.
     *
     * @return Collection<int, Product>
     */
    public function all(): Collection
    {
        return Product::with('category')->orderBy('name')->get();
    }

    /**
     * Get paginated and filtered products.
     */
    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $query = Product::with('category');

        if ($search !== null && $search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('category', static function ($catQuery) use ($search): void {
                        $catQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find a product by ID.
     */
    public function findById(int $id): ?Product
    {
        return Product::with('category')->find($id);
    }

    /**
     * Create a new product.
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }
}
