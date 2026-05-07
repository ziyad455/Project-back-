<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final class GoogleAuthService
{
    public function findOrCreateUser(SocialiteUser $googleUser, string $role = 'client'): User
    {
        $email = $googleUser->getEmail();

        if (! $email) {
            throw ValidationException::withMessages([
                'email' => ['Google did not return an email address for this account.'],
            ]);
        }

        [$firstName, $lastName] = $this->resolveNames($googleUser);

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        }

        return User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(40)),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'role' => $role,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveNames(SocialiteUser $googleUser): array
    {
        $rawUser = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];
        $name = trim((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User'));
        $parts = preg_split('/\s+/', $name, 2) ?: ['Google'];

        return [
            trim((string) ($rawUser['given_name'] ?? '')) ?: $parts[0],
            trim((string) ($rawUser['family_name'] ?? '')) ?: ($parts[1] ?? 'User'),
        ];
    }
}
