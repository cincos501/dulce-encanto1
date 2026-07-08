<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\PromotionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionRequest;
use App\Http\Resources\PromotionResource;
use App\Services\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PromotionController extends Controller
{
    public function __construct(
        protected PromotionService $promotionService
    ) {}

    /**
     * Display a listing of promotions.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);
        $paginate = filter_var($request->query('paginate', true), FILTER_VALIDATE_BOOLEAN);

        if (! $paginate) {
            $promotions = $this->promotionService->all()->load('variants.product');

            return response()->json([
                'success' => true,
                'message' => 'Promociones recuperadas con éxito.',
                'data' => PromotionResource::collection($promotions),
            ]);
        }

        $promotions = $this->promotionService->paginate($perPage, $search);
        $promotions->getCollection()->load('variants.product');

        return PromotionResource::collection($promotions)->additional([
            'success' => true,
            'message' => 'Promociones recuperadas con éxito.',
        ]);
    }

    /**
     * Store a newly created promotion in storage.
     */
    public function store(PromotionRequest $request): JsonResponse|PromotionResource
    {
        try {
            $dto = PromotionDTO::fromArray($request->validated());
            $promotion = $this->promotionService->create($dto, $request->input('product_variant_ids', []));

            return (new PromotionResource($promotion->load('variants.product')))->additional([
                'success' => true,
                'message' => 'Promoción creada con éxito.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 422);
        }
    }

    /**
     * Display the specified promotion.
     */
    public function show(int $id): PromotionResource
    {
        $promotion = $this->promotionService->findById($id)->load('variants.product');

        return (new PromotionResource($promotion))->additional([
            'success' => true,
            'message' => 'Promoción recuperada con éxito.',
        ]);
    }

    /**
     * Update the specified promotion in storage.
     */
    public function update(PromotionRequest $request, int $id): JsonResponse|PromotionResource
    {
        try {
            $dto = PromotionDTO::fromArray($request->validated());
            $promotion = $this->promotionService->update($id, $dto, $request->input('product_variant_ids', []));

            return (new PromotionResource($promotion->load('variants.product')))->additional([
                'success' => true,
                'message' => 'Promoción actualizada con éxito.',
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 422);
        }
    }

    /**
     * Toggle the active state of the specified promotion.
     */
    public function toggleActive(int $id): JsonResponse|PromotionResource
    {
        try {
            $promotion = $this->promotionService->toggleActive($id);

            return (new PromotionResource($promotion->load('variants.product')))->additional([
                'success' => true,
                'message' => 'Estado de la promoción actualizado con éxito.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 422);
        }
    }

    /**
     * Remove the specified promotion from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->promotionService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Promoción eliminada con éxito.',
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
