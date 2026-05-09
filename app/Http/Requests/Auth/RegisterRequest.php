<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('role') === 'stagiaire') {
            $this->merge(['role' => 'provider']);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(['client', 'provider'])],
            'bio' => ['nullable', 'string', 'max:1000'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'skills' => ['nullable'], // allow string or array
            'university' => ['nullable', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'document_student_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // max 5MB
        ];
    }
}
