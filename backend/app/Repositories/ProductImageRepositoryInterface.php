<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductImage;
use Illuminate\Support\Collection;

interface ProductImageRepositoryInterface
{
    /**
     * Find an image by ID.
     */
    public function findById(int $id): ?ProductImage;

    /**
     * Get all images for a variant.
     *
     * @return Collection<int, ProductImage>
     */
    public function getByVariantId(int $variantId): Collection;

    /**
     * Create a new image record.
     */
    public function create(array $data): ProductImage;

    /**
     * Set all other images of a variant to NOT primary.
     */
    public function clearPrimaryStatus(int $variantId, int $exceptImageId): void;

    /**
     * Delete an image record.
     */
    public function delete(ProductImage $image): bool;
}
