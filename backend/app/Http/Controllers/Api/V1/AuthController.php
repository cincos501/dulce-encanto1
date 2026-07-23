<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El formato del correo es inválido.',
            'email.exists' => 'No encontramos ningún usuario con ese correo electrónico.',
        ]);

        $message = $this->authService->sendResetLink($credentials);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'token.required' => 'El token es requerido.',
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El formato del correo es inválido.',
            'email.exists' => 'El correo seleccionado no existe.',
            'password.required' => 'La nueva contraseña es requerida.',
            'password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $message = $this->authService->resetPassword($credentials);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}
