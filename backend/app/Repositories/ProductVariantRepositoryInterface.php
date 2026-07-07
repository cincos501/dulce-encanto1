<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductVariantRepositoryInterface
{
    /**
     * Get all product variants.
     *
     * @return Collection<int, ProductVariant>
     */
    public function all(): Collection;

    /**
     * Get variants by product ID.
     *
     * @return Collection<int, ProductVariant>
     */
    public function getByProductId(int $productId): Collection;

    /**
     * Get paginated and filtered variants.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?int $productId = null): LengthAwarePaginator;

    /**
     * Find a variant by ID.
     */
    public function findById(int $id): ?ProductVariant;

    /**
     * Create a new variant.
     */
    public function create(array $data): ProductVariant;

    /**
     * Update an existing variant.
     */
    public function update(ProductVariant $variant, array $data): ProductVariant;

    /**
     * Delete a variant.
     */
    public function delete(ProductVariant $variant): bool;
}
