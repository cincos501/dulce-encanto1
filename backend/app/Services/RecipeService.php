<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\RecipeDTO;
use App\Models\ProductVariant;
use App\Repositories\RecipeRepositoryInterface;
use App\Repositories\ProductVariantRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RecipeService
{
    public function __construct(
        protected RecipeRepositoryInterface $recipeRepository,
        protected ProductVariantRepositoryInterface $variantRepository
    ) {}

    /**
     * Get paginated recipes.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator
    {
        return $this->recipeRepository->paginate($perPage, $search, $onlyActive);
    }

    /**
     * Get all recipes.
     *
     * @return Collection<int, ProductVariant>
     */
    public function all(bool $onlyActive = false): Collection
    {
        return $this->recipeRepository->all($onlyActive);
    }

    /**
     * Find a variant's recipe by ID or throw exception.
     */
    public function findVariantWithRecipe(int $variantId): ProductVariant
    {
        $variant = $this->recipeRepository->findVariantWithRecipe($variantId);

        if ($variant === null) {
            throw (new ModelNotFoundException)->setModel(ProductVariant::class, [$variantId]);
        }

        return $variant;
    }

    /**
     * Save / Sync a recipe for a product variant.
     */
    public function saveRecipe(RecipeDTO $dto): ProductVariant
    {
        // 1. Fetch variant and assert exists and is active
        $variant = $this->variantRepository->findById($dto->product_variant_id);
        if ($variant === null) {
            throw (new ModelNotFoundException)->setModel(ProductVariant::class, [$dto->product_variant_id]);
        }

        if (! $variant->is_active) {
            throw new \Exception('No se puede configurar una receta para una presentación inactiva.');
        }

        // 2. Prepare items for sync
        $itemsData = [];
        foreach ($dto->items as $item) {
            $itemsData[] = [
                'supply_id' => $item->supply_id,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'observation' => $item->observation,
            ];
        }

        // 3. Delegate to repository
        $this->recipeRepository->syncRecipe($variant, $itemsData);

        return $this->findVariantWithRecipe($variant->id);
    }
}
