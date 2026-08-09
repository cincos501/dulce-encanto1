<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\StoreProductionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductionRequest;
use App\Http\Resources\ProductionBatchResource;
use App\Services\ProductionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ProductionController extends Controller
{
    public function __construct(
        protected ProductionService $productionService
    ) {}

    /**
     * Display a listing of manual production batches.
     */
    public function index(): JsonResponse
    {
        $batches = $this->productionService->getProductionHistory();
        return response()->json([
            'success' => true,
            'data' => ProductionBatchResource::collection($batches),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
            ]
        ]);
    }

    /**
     * Store a newly created manual production batch.
     */
    public function store(StoreProductionRequest $request): JsonResponse
    {
        try {
            $dto = StoreProductionDTO::fromArray($request->validated());
            $batch = $this->productionService->recordProduction($dto);

            Log::info("Manual production batch logged successfully", [
                'batch_id' => $batch->id,
                'variant_id' => $batch->product_variant_id,
                'quantity' => $batch->quantity,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lote de producción manual registrado correctamente.',
                'data' => new ProductionBatchResource($batch->load('productVariant.product')),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación al registrar producción.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error("Failed to log manual production batch", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado al procesar la producción manual.',
            ], 500);
        }
    }
}
