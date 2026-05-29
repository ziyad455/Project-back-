<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\TalentRegisterRequest;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register([
            ...$request->validated(),
            'role' => 'client',
        ]);

        return $this->authenticatedResponse($request, $user, 'Inscription réussie', 201);
    }

    public function registerTalent(TalentRegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register([
            ...$request->validated(),
            'role' => 'provider',
        ]);

        return $this->authenticatedResponse($request, $user, 'Inscription talent soumise pour vérification', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->authenticate($request->validated());

        return $this->authenticatedResponse($request, $user, 'Connexion réussie');
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()?->currentAccessToken() instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $request->user()->currentAccessToken()->delete();
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return ApiResponse::success(message: 'Déconnexion réussie');
    }

    public function user(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => $request->user()->load(['categories.translations', 'translations']),
        ], 'Utilisateur authentifié');
    }

    private function authenticatedResponse(Request $request, User $user, string $message, int $status = 200): JsonResponse
    {
        Auth::guard('web')->login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return ApiResponse::success([
            'user' => $user->load(['categories.translations', 'translations']),
            'access_token' => $this->authService->issueToken($user),
            'token_type' => 'Bearer',
        ], $message, $status);
    }
}
