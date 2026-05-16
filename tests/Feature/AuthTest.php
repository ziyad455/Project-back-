<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'whatsapp_number' => '0600000000',
            'city' => 'Marrakech',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'user' => ['id', 'first_name', 'last_name', 'email', 'role'],
                         'access_token',
                         'token_type',
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'client',
        ]);
    }

    public function test_client_registration_ignores_provider_role()
    {
        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Jane',
            'last_name' => 'Client',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'provider',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.role', 'client');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'role' => 'client',
        ]);
    }

    public function test_talent_registration_uses_dedicated_endpoint()
    {
        Storage::fake('public');

        $response = $this->post('/api/auth/register/talent', [
            'first_name' => 'Sara',
            'last_name' => 'Talent',
            'email' => 'sara@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'whatsapp_number' => '0600000001',
            'city' => 'Marrakech',
            'bio' => 'Full stack student developer.',
            'hourly_rate' => 150,
            'skills' => 'Laravel, React',
            'university' => 'Cadi Ayyad',
            'field_of_study' => 'Computer Science',
            'document_student_proof' => UploadedFile::fake()->create('student-card.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.role', 'provider')
            ->assertJsonPath('data.user.is_verified_student', false);

        $this->assertDatabaseHas('users', [
            'email' => 'sara@example.com',
            'role' => 'provider',
            'is_verified_student' => false,
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('secret123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'user',
                         'access_token',
                         'token_type',
                     ],
                 ]);
    }

    public function test_frontend_session_can_authenticate_after_login()
    {
        User::factory()->create([
            'email' => 'session@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->withHeader('Origin', 'http://localhost:5173')
            ->get('/sanctum/csrf-cookie');

        $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/auth/login', [
                'email' => 'session@example.com',
                'password' => 'secret123',
            ])
            ->assertStatus(200);

        $this->withHeader('Origin', 'http://localhost:5173')
            ->getJson('/api/auth/user')
            ->assertStatus(200)
            ->assertJsonPath('data.user.email', 'session@example.com');
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Déconnexion réussie',
                     'data' => [],
                 ]);

        $this->assertCount(0, $user->tokens);
    }
}
