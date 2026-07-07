<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = $request->input('search');
        $role = $request->input('role');
        $isActive = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : null;

        $users = $this->userService->paginate($perPage, $search, $role, $isActive);

        return response()->json([
            'success' => true,
            'message' => 'Usuarios recuperados con éxito.',
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $dto = UserDTO::fromArray($request->validated());
        $user = $this->userService->createUser($dto);

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado con éxito.',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Usuario recuperado con éxito.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        try {
            $dto = UserDTO::fromArray($request->validated());
            $updatedUser = $this->userService->updateUser($user, $dto);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado con éxito.',
                'data' => new UserResource($updatedUser),
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
     * Remove the specified resource from storage (soft deactivation).
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Usuario desactivado con éxito.',
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
     * Reset password for the specified user.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ], [], [
            'password' => 'contraseña',
        ]);

        $this->userService->resetPassword($user, $request->input('password'));

        return response()->json([
            'success' => true,
            'message' => 'Contraseña restablecida con éxito.',
        ]);
    }
}
