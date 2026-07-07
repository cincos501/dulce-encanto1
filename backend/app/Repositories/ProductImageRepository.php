<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductImage;
use Illuminate\Support\Collection;

class ProductImageRepository implements ProductImageRepositoryInterface
{
    /**
     * Find an image by ID.
     */
    public function findById(int $id): ?ProductImage
    {
        return ProductImage::find($id);
    }

    /**
     * Get all images for a variant.
     *
     * @return Collection<int, ProductImage>
     */
    public function getByVariantId(int $variantId): Collection
    {
        return ProductImage::where('product_variant_id', $variantId)->get();
    }

    /**
     * Create a new image record.
     */
    public function create(array $data): ProductImage
    {
        return ProductImage::create($data);
    }

    /**
     * Set all other images of a variant to NOT primary.
     */
    public function clearPrimaryStatus(int $variantId, int $exceptImageId): void
    {
        ProductImage::where('product_variant_id', $variantId)
            ->where('id', '!=', $exceptImageId)
            ->update(['is_primary' => false]);
    }

    /**
     * Delete an image record.
     */
    public function delete(ProductImage $image): bool
    {
        return (bool) $image->delete();
    }
}
