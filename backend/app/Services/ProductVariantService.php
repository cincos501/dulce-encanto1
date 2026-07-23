<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ProductVariantDTO;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\ProductVariantRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductVariantService
{
    public function __construct(
        protected ProductVariantRepositoryInterface $variantRepository
    ) {}

    /**
     * Get paginated and filtered variants.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?int $productId = null, bool $onlyActive = false): LengthAwarePaginator
    {
        return $this->variantRepository->paginate($perPage, $search, $productId, $onlyActive);
    }

    /**
     * Get all variants.
     *
     * @return Collection<int, ProductVariant>
     */
    public function all(bool $onlyActive = false): Collection
    {
        return $this->variantRepository->all($onlyActive);
    }

    /**
     * Find a variant by ID or throw exception.
     */
    public function findById(int $id): ProductVariant
    {
        $variant = $this->variantRepository->findById($id);

        if ($variant === null) {
            throw (new ModelNotFoundException)->setModel(ProductVariant::class, [$id]);
        }

        return $variant;
    }

    /**
     * Create a new variant.
     */
    public function create(ProductVariantDTO $dto): ProductVariant
    {
        $sku = $this->generateUniqueSku($dto->product_id, $dto->name);

        $data = $dto->toArray();
        $data['sku'] = $sku;

        $variant = $this->variantRepository->create($data);

        $syncData = [];
        if (!empty($dto->extras)) {
            foreach ($dto->extras as $extraItem) {
                $syncData[(int) $extraItem['extra_id']] = ['price' => (float) $extraItem['price']];
            }
        }
        $variant->extras()->sync($syncData);

        return $variant;
    }

    /**
     * Update an existing variant.
     */
    public function update(int $id, ProductVariantDTO $dto): ProductVariant
    {
        $variant = $this->findById($id);

        $data = $dto->toArray();
        // Do not update SKU, keep the original one
        $data['sku'] = $variant->sku;

        $updatedVariant = $this->variantRepository->update($variant, $data);

        $syncData = [];
        if (!empty($dto->extras)) {
            foreach ($dto->extras as $extraItem) {
                $syncData[(int) $extraItem['extra_id']] = ['price' => (float) $extraItem['price']];
            }
        }
        $updatedVariant->extras()->sync($syncData);

        return $updatedVariant;
    }

    /**
     * Toggle the active state of a variant.
     */
    public function toggleActive(int $id): ProductVariant
    {
        $variant = $this->findById($id);

        return $this->variantRepository->update($variant, [
            'is_active' => ! $variant->is_active,
        ]);
    }

    /**
     * Delete a variant checking business rules.
     *
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $variant = $this->findById($id);

        // Business Rule: A product variant cannot be deleted if it has been used in registered orders (historical commercial records).
        if ($variant->orderItems()->exists()) {
            throw new \Exception('No se puede eliminar la variante porque ha sido vendida en pedidos registrados.');
        }

        // Clean up relations
        if (method_exists($variant, 'recipes')) {
            $variant->recipes()->delete();
        }
        if (method_exists($variant, 'extras')) {
            $variant->extras()->detach();
        }
        if (method_exists($variant, 'promotions')) {
            $variant->promotions()->detach();
        }
        if (method_exists($variant, 'images')) {
            $variant->images()->delete();
        }

        return $this->variantRepository->delete($variant);
    }

    /**
     * Generate a unique SKU based on product and variant presentation name.
     */
    protected function generateUniqueSku(int $productId, string $variantName): string
    {
        $product = Product::find($productId);
        $prodName = $product ? $product->name : 'PROD';

        // Strip non-alphanumeric and uppercase
        $cleanProduct = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prodName));
        $cleanVariant = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $variantName));

        $prefix = substr($cleanProduct, 0, 4).'-'.substr($cleanVariant, 0, 4);

        $sku = $prefix;
        $counter = 1;
        while (ProductVariant::where('sku', $sku)->exists()) {
            $sku = $prefix.'-'.str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }
}
