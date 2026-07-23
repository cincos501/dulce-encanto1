<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\ProductVariantDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Services\ProductVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductVariantController extends Controller
{
    public function __construct(
        protected ProductVariantService $variantService
    ) {}

    /**
     * Display a listing of variants.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);
        $productId = $request->query('product_id') ? (int) $request->query('product_id') : null;
        $paginate = filter_var($request->query('paginate', true), FILTER_VALIDATE_BOOLEAN);
        $onlyActive = filter_var($request->query('only_active', false), FILTER_VALIDATE_BOOLEAN);

        if (! $paginate) {
            $variants = $productId !== null
                ? $this->variantService->paginate(9999, null, $productId, $onlyActive)->getCollection()
                : $this->variantService->all($onlyActive);

            return ProductVariantResource::collection($variants->load(['product', 'extras']))->additional([
                'success' => true,
                'message' => 'Variantes recuperadas con éxito.',
            ]);
        }

        $variants = $this->variantService->paginate($perPage, $search, $productId, $onlyActive);
        $variants->getCollection()->load(['product', 'extras']);

        return ProductVariantResource::collection($variants)->additional([
            'success' => true,
            'message' => 'Variantes recuperadas con éxito.',
        ]);
    }

    /**
     * Store a newly created variant in storage.
     */
    public function store(ProductVariantRequest $request): ProductVariantResource
    {
        $dto = ProductVariantDTO::fromArray($request->validated());
        $variant = $this->variantService->create($dto);

        return (new ProductVariantResource($variant->load(['product', 'extras'])))->additional([
            'success' => true,
            'message' => 'Variante de producto creada con éxito.',
        ]);
    }

    /**
     * Display the specified variant.
     */
    public function show(int $id): ProductVariantResource
    {
        $variant = $this->variantService->findById($id);

        return (new ProductVariantResource($variant->load(['product', 'extras'])))->additional([
            'success' => true,
            'message' => 'Variante recuperada con éxito.',
        ]);
    }

    /**
     * Update the specified variant in storage.
     */
    public function update(ProductVariantRequest $request, int $id): ProductVariantResource
    {
        $dto = ProductVariantDTO::fromArray($request->validated());
        $variant = $this->variantService->update($id, $dto);

        return (new ProductVariantResource($variant->load(['product', 'extras'])))->additional([
            'success' => true,
            'message' => 'Variante actualizada con éxito.',
        ]);
    }

    /**
     * Toggle the active state of the specified variant.
     */
    public function toggleActive(int $id): ProductVariantResource
    {
        $variant = $this->variantService->toggleActive($id);

        return (new ProductVariantResource($variant->load(['product', 'extras'])))->additional([
            'success' => true,
            'message' => 'Estado de la variante actualizado con éxito.',
        ]);
    }

    /**
     * Remove the specified variant from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->variantService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Variante de producto eliminada con éxito.',
                'data' => (object) [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 422);
        }
    }
}
