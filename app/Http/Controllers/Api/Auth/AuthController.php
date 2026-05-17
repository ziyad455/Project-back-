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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

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

        $this->authService->sendOtp($user);

        return ApiResponse::success([
            'email' => $user->email,
            'requires_verification' => true,
        ], 'Inscription réussie. Veuillez vérifier votre adresse e-mail avec le code OTP.', 201);
    }

    public function registerTalent(TalentRegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register([
            ...$request->validated(),
            'role' => 'provider',
        ]);

        $this->authService->sendOtp($user);

        return ApiResponse::success([
            'email' => $user->email,
            'requires_verification' => true,
        ], 'Inscription talent réussie. Veuillez vérifier votre adresse e-mail avec le code OTP.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->authenticate($request->validated());

        if ($user->email_verified_at === null) {
            return ApiResponse::error(
                message: 'Votre adresse e-mail n\'est pas encore vérifiée.',
                status: 403,
                data: [
                    'email_unverified' => true,
                    'email' => $user->email,
                    'errors' => [
                        'email' => ['Votre adresse e-mail n\'est pas encore vérifiée.']
                    ]
                ]
            );
        }

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

    /**
     * Verify OTP code for a user.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return ApiResponse::error(message: 'Utilisateur introuvable.', status: 404);
        }

        $this->authService->verifyOtp($user, $request->input('otp_code'));

        return $this->authenticatedResponse($request, $user, 'Vérification de l\'adresse e-mail réussie.');
    }

    /**
     * Resend OTP code for a user with rate limit checks.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return ApiResponse::error(message: 'Utilisateur introuvable.', status: 404);
        }

        if ($user->email_verified_at !== null) {
            return ApiResponse::success(message: 'Votre adresse e-mail est déjà vérifiée.');
        }

        // Spam protection check (60 seconds cooldown)
        $latest = \DB::table('email_verifications')->where('user_id', $user->id)->first();
        if ($latest && \Illuminate\Support\Carbon::parse($latest->updated_at)->addMinute()->isFuture()) {
            return ApiResponse::error(
                message: 'Veuillez patienter 60 secondes avant de demander un nouveau code.',
                status: 429
            );
        }

        $this->authService->sendOtp($user);

        return ApiResponse::success(message: 'Un nouveau code de vérification a été envoyé.');
    }

    /**
     * Send password reset link to user. Protects against email enumeration.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            // Spam protection check (60 seconds cooldown)
            $latest = \DB::table('password_reset_tokens')->where('email', $user->email)->first();
            if ($latest && \Illuminate\Support\Carbon::parse($latest->created_at)->addMinute()->isFuture()) {
                return ApiResponse::error(
                    message: 'Veuillez patienter 60 secondes avant de demander un nouveau lien.',
                    status: 429
                );
            }

            // Use native Password Broker
            Password::broker()->sendResetLink($request->only('email'));
        }

        return ApiResponse::success(message: 'Si cette adresse e-mail est enregistrée, un lien de réinitialisation vous a été envoyé.');
    }

    /**
     * Reset the user's password using the token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return ApiResponse::success(message: 'Votre mot de passe a été réinitialisé avec succès.');
        }

        $errorMessage = match($status) {
            Password::INVALID_USER => "Utilisateur introuvable.",
            Password::INVALID_TOKEN => "Le lien de réinitialisation est invalide ou a expiré.",
            Password::INVALID_PASSWORD => "Le mot de passe doit respecter les critères de validation.",
            Password::RESET_THROTTLED => "Trop de tentatives de réinitialisation. Veuillez patienter.",
            default => __($status)
        };

        return ApiResponse::error(message: $errorMessage, status: 400);
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
