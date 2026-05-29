<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_provider_student_proof_document(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('student_proofs/proof.pdf', '%PDF-1.4 test document');

        $admin = User::factory()->create([
            'role' => 'client',
            'is_admin' => true,
        ]);

        $provider = User::factory()->create([
            'role' => 'provider',
            'document_student_proof' => 'student_proofs/proof.pdf',
            'is_verified_student' => false,
        ]);

        $response = $this->actingAs($admin)->get("/api/admin/providers/{$provider->id}/documents/student-proof");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_non_admin_cannot_view_provider_documents(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('student_proofs/proof.pdf', '%PDF-1.4 test document');

        $client = User::factory()->create([
            'role' => 'client',
            'is_admin' => false,
        ]);

        $provider = User::factory()->create([
            'role' => 'provider',
            'document_student_proof' => 'student_proofs/proof.pdf',
        ]);

        $this->actingAs($client)
            ->getJson("/api/admin/providers/{$provider->id}/documents/student-proof")
            ->assertForbidden();
    }
}
