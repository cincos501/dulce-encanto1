<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ProductImageDTO;
use App\Models\ProductImage;
use App\Repositories\ProductImageRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class ProductImageService
{
    public function __construct(
        protected ProductImageRepositoryInterface $imageRepository,
        protected StorageServiceInterface $storageService
    ) {}

    /**
     * Get all images for a variant.
     *
     * @return Collection<int, ProductImage>
     */
    public function getByVariantId(int $variantId): Collection
    {
        return $this->imageRepository->getByVariantId($variantId);
    }

    /**
     * Find an image or throw ModelNotFoundException.
     */
    public function findById(int $id): ProductImage
    {
        $image = $this->imageRepository->findById($id);

        if ($image === null) {
            throw (new ModelNotFoundException)->setModel(ProductImage::class, [$id]);
        }

        return $image;
    }

    /**
     * Upload and store product image.
     */
    public function storeImage(int $variantId, UploadedFile $file, bool $isPrimary): ProductImage
    {
        // Check if this is the first image for this variant
        $existingImages = $this->imageRepository->getByVariantId($variantId);
        if ($existingImages->isEmpty()) {
            $isPrimary = true;
        }

        // Store file using the storage service
        $path = $this->storageService->upload('variants', $file);

        $dto = new ProductImageDTO(
            product_variant_id: $variantId,
            image_path: $path,
            is_primary: $isPrimary
        );

        $image = $this->imageRepository->create($dto->toArray());

        if ($isPrimary) {
            $this->imageRepository->clearPrimaryStatus($variantId, $image->id);
        }

        return $image;
    }

    /**
     * Mark an image as primary.
     */
    public function setPrimary(int $id): ProductImage
    {
        $image = $this->findById($id);

        $image->is_primary = true;
        $image->save();

        $this->imageRepository->clearPrimaryStatus($image->product_variant_id, $image->id);

        return $image;
    }

    /**
     * Delete an image.
     */
    public function delete(int $id): bool
    {
        $image = $this->findById($id);
        $variantId = $image->product_variant_id;
        $wasPrimary = $image->is_primary;

        // Delete file using storage service
        $this->storageService->delete($image->image_url);

        $deleted = $this->imageRepository->delete($image);

        // If the deleted image was primary, mark the first remaining image of the variant as primary
        if ($deleted && $wasPrimary) {
            $remaining = $this->imageRepository->getByVariantId($variantId);
            if ($remaining->isNotEmpty()) {
                $first = $remaining->first();
                $first->is_primary = true;
                $first->save();
            }
        }

        return $deleted;
    }
}
