<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Services\ProductImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductImageController extends Controller
{
    public function __construct(
        protected ProductImageService $imageService
    ) {}

    /**
     * Display a listing of images for a variant.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $variantId = $request->query('product_variant_id');
        if ($variantId === null) {
            return response()->json([
                'success' => false,
                'message' => 'El parámetro product_variant_id es requerido.',
                'errors' => [],
            ], 422);
        }

        $images = $this->imageService->getByVariantId((int) $variantId);

        return ProductImageResource::collection($images)->additional([
            'success' => true,
            'message' => 'Imágenes de la variante recuperadas con éxito.',
        ]);
    }

    /**
     * Store a newly uploaded image.
     */
    public function store(ProductImageRequest $request): ProductImageResource
    {
        $variantId = (int) $request->input('product_variant_id');
        $file = $request->file('image');
        $isPrimary = filter_var($request->input('is_primary', false), FILTER_VALIDATE_BOOLEAN);

        $image = $this->imageService->storeImage($variantId, $file, $isPrimary);

        return (new ProductImageResource($image))->additional([
            'success' => true,
            'message' => 'Imagen cargada con éxito.',
        ]);
    }

    /**
     * Set image as primary.
     */
    public function setPrimary(int $id): ProductImageResource
    {
        $image = $this->imageService->setPrimary($id);

        return (new ProductImageResource($image))->additional([
            'success' => true,
            'message' => 'Imagen establecida como principal con éxito.',
        ]);
    }

    /**
     * Remove the specified image.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->imageService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada con éxito.',
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
