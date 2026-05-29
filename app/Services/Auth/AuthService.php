<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuthService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): User
    {
        [$firstName, $lastName] = $this->resolveNames($data);

        $skills = $data['skills'] ?? null;
        if (is_string($skills)) {
            $skills = array_filter(array_map('trim', explode(',', $skills)));
        }

        $documentPath = null;
        if (isset($data['document_student_proof']) && $data['document_student_proof'] instanceof \Illuminate\Http\UploadedFile) {
            $documentPath = $data['document_student_proof']->store('student_proofs', 'public');
        }

        $idCardPath = null;
        if (isset($data['document_id_card']) && $data['document_id_card'] instanceof \Illuminate\Http\UploadedFile) {
            $idCardPath = $data['document_id_card']->store('student_proofs', 'public');
        }

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'city' => $data['city'] ?? null,
            'role' => $data['role'],
            'bio' => $data['bio'] ?? null,

            'university' => $data['university'] ?? null,
            'field_of_study' => $data['field_of_study'] ?? null,
            'portfolio_url' => $data['portfolio_url'] ?? null,
            'skills' => $skills,
            'document_student_proof' => $documentPath,
            'document_id_card' => $idCardPath,
        ]);

        // Force translation generation for new users (the saved event handler may
        // be unreliable, so we call it explicitly here).
        try {
            $user->generateTranslations();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Translation generation failed for new user: ' . $e->getMessage());
        }

        return $user->fresh()->load('translations');
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function authenticate(array $credentials): User
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        return $user;
    }

    public function issueToken(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string}
     */
    private function resolveNames(array $data): array
    {
        $fallbackName = trim((string) preg_replace('/[^A-Za-z0-9]+/', ' ', Str::before($data['email'], '@')));
        $fallbackName = $fallbackName !== '' ? Str::title($fallbackName) : 'New User';
        $parts = preg_split('/\s+/', $fallbackName, 2) ?: ['New'];

        $firstName = trim((string) ($data['first_name'] ?? '')) ?: $parts[0];
        $lastName = trim((string) ($data['last_name'] ?? '')) ?: ($parts[1] ?? ($data['role'] === 'client' ? 'Client' : 'Provider'));

        return [$firstName, $lastName];
    }
}
