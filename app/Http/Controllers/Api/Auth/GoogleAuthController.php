<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

final class GoogleAuthController extends Controller
{
    public function __construct(private readonly GoogleAuthService $googleAuthService)
    {
    }

    public function redirect(Request $request): RedirectResponse
    {
        if ($request->hasSession()) {
            $request->session()->put('oauth_role', 'client');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $role = $request->hasSession() ? $request->session()->pull('oauth_role', 'client') : 'client';
            $user = $this->googleAuthService->findOrCreateUser($googleUser, $this->resolveRole($role));

            Auth::guard('web')->login($user);

            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            return redirect()->away($this->frontendUrl('/talents'));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->away($this->frontendUrl('/login?oauth_error=google'));
        }
    }

    private function frontendUrl(string $path): string
    {
        return rtrim((string) config('services.frontend.url'), '/').$path;
    }

    private function resolveRole(mixed $role): string
    {
        return Arr::first(['client'], fn (string $allowedRole): bool => $allowedRole === $role) ?? 'client';
    }
}
