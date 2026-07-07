<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Authenticate user and initiate session.
     */
    public function login(LoginRequest $request): UserResource
    {
        $user = $this->authService->login($request->validated());

        return (new UserResource($user))->additional([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
        ]);
    }

    /**
     * Terminate user session.
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
            'data' => (object) [],
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(): UserResource
    {
        /** @var User $user */
        $user = Auth::user();

        return (new UserResource($user))->additional([
            'success' => true,
            'message' => 'Usuario recuperado con éxito.',
        ]);
    }
}
