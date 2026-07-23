<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\ExtraDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExtraRequest;
use App\Http\Resources\ExtraResource;
use App\Services\ExtraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExtraController extends Controller
{
    public function __construct(
        protected ExtraService $extraService
    ) {}

    /**
     * Display a listing of extras.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);
        $paginate = filter_var($request->query('paginate', true), FILTER_VALIDATE_BOOLEAN);
        $onlyActive = filter_var($request->query('only_active', false), FILTER_VALIDATE_BOOLEAN);

        if (! $paginate) {
            $extras = $this->extraService->all($onlyActive);

            return response()->json([
                'success' => true,
                'message' => 'Extras recuperados con éxito.',
                'data' => ExtraResource::collection($extras),
            ]);
        }

        $extras = $this->extraService->paginate($perPage, $search, $onlyActive);

        return ExtraResource::collection($extras)->additional([
            'success' => true,
            'message' => 'Extras recuperados con éxito.',
        ]);
    }

    /**
     * Store a newly created extra in storage.
     */
    public function store(ExtraRequest $request): ExtraResource
    {
        $dto = ExtraDTO::fromArray($request->validated());
        $extra = $this->extraService->create($dto);

        return (new ExtraResource($extra))->additional([
            'success' => true,
            'message' => 'Extra creado con éxito.',
        ]);
    }

    /**
     * Display the specified extra.
     */
    public function show(int $id): ExtraResource
    {
        $extra = $this->extraService->findById($id);

        return (new ExtraResource($extra))->additional([
            'success' => true,
            'message' => 'Extra recuperado con éxito.',
        ]);
    }

    /**
     * Update the specified extra in storage.
     */
    public function update(ExtraRequest $request, int $id): ExtraResource
    {
        $dto = ExtraDTO::fromArray($request->validated());
        $extra = $this->extraService->update($id, $dto);

        return (new ExtraResource($extra))->additional([
            'success' => true,
            'message' => 'Extra actualizado con éxito.',
        ]);
    }

    /**
     * Toggle the active state of the specified extra.
     */
    public function toggleActive(int $id): ExtraResource
    {
        $extra = $this->extraService->toggleActive($id);

        return (new ExtraResource($extra))->additional([
            'success' => true,
            'message' => 'Estado del extra actualizado con éxito.',
        ]);
    }

    /**
     * Remove the specified extra from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->extraService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Extra eliminado con éxito.',
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
