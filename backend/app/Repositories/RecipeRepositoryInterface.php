<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RecipeRepositoryInterface
{
    /**
     * Get all variants with recipes.
     *
     * @return Collection<int, ProductVariant>
     */
    public function all(bool $onlyActive = false): Collection;

    /**
     * Get paginated variants with recipes.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator;

    /**
     * Find a variant with its recipe items.
     */
    public function findVariantWithRecipe(int $variantId): ?ProductVariant;

    /**
     * Sync recipe items for a variant.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function syncRecipe(ProductVariant $variant, array $items): void;
}
