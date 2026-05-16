<?php

namespace Tests\Feature;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_service_request()
    {
        $client = User::factory()->create(['role' => 'client']);
        $category = ServiceCategory::create(['name' => 'Plumbing']);

        $response = $this->actingAs($client, 'sanctum')->postJson('/api/requests', [
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('service_requests', [
            'client_id' => $client->id,
            'description' => 'Fix my sink',
        ]);
    }

    public function test_guest_can_create_service_request()
    {
        $category = ServiceCategory::create(['name' => 'Plumbing']);

        $response = $this->postJson('/api/requests', [
            'guest_name' => 'Guest Client',
            'guest_email' => 'guest@example.com',
            'guest_whatsapp_number' => '0600000000',
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
        ]);

        $response->assertStatus(201);
        $response->assertJsonMissingPath('data.guest_whatsapp_number');

        $this->assertDatabaseHas('service_requests', [
            'client_id' => null,
            'guest_name' => 'Guest Client',
            'guest_whatsapp_number' => '0600000000',
            'description' => 'Fix my sink',
        ]);
    }

    public function test_provider_can_place_offer()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->create(['role' => 'provider', 'is_verified_student' => true]);
        $category = ServiceCategory::create(['name' => 'Plumbing']);
        $request = ServiceRequest::create([
            'client_id' => $client->id,
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider, 'sanctum')->postJson("/api/requests/{$request->id}/offers", [
            'offered_price' => 250,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('request_offers', [
            'service_request_id' => $request->id,
            'provider_id' => $provider->id,
            'offered_price' => 250,
        ]);
    }

    public function test_provider_can_view_own_offers()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->create(['role' => 'provider', 'is_verified_student' => true]);
        $category = ServiceCategory::create(['name' => 'Plumbing']);
        $request = ServiceRequest::create([
            'client_id' => $client->id,
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
            'status' => 'pending',
        ]);

        $request->offers()->create([
            'provider_id' => $provider->id,
            'offered_price' => 250,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider, 'sanctum')->getJson('/api/offers/my');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.provider_id', $provider->id)
            ->assertJsonPath('data.0.service_request.category.name', 'Plumbing');
    }

    public function test_provider_can_view_talent_stats()
    {
        $provider = User::factory()->create([
            'role' => 'provider',
            'is_verified_student' => true,
            'city' => 'Marrakech',
            'completed_jobs' => 2,
            'average_rating' => 4.5,
            'total_votes' => 3,
        ]);
        $category = ServiceCategory::create(['name' => 'Plumbing']);
        ServiceRequest::create([
            'guest_name' => 'Guest Client',
            'guest_whatsapp_number' => '0600000000',
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider, 'sanctum')->getJson('/api/talent/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.completed_jobs', 2)
            ->assertJsonPath('data.open_requests', 1)
            ->assertJsonPath('data.is_verified_student', true);
    }

    public function test_unverified_provider_cannot_place_offer()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->create(['role' => 'provider', 'is_verified_student' => false]);
        $category = ServiceCategory::create(['name' => 'Plumbing']);
        $request = ServiceRequest::create([
            'client_id' => $client->id,
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider, 'sanctum')->postJson("/api/requests/{$request->id}/offers", [
            'offered_price' => 250,
        ]);

        $response->assertStatus(403);
    }

    public function test_talent_can_accept_mission()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->create(['role' => 'provider', 'is_verified_student' => true]);
        $category = ServiceCategory::create(['name' => 'Plumbing']);
        $request = ServiceRequest::create([
            'client_id' => $client->id,
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider, 'sanctum')->postJson("/api/requests/{$request->id}/offers", [
            'message' => 'I can do this',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('request_offers', [
            'service_request_id' => $request->id,
            'provider_id' => $provider->id,
            'status' => 'accepted',
        ]);
        $this->assertEquals('in_progress', $request->fresh()->status);
        $this->assertEquals($provider->id, $request->fresh()->selected_provider_id);
    }

    public function test_talent_can_refuse_mission()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->create(['role' => 'provider', 'is_verified_student' => true]);
        $category = ServiceCategory::create(['name' => 'Plumbing']);
        $request = ServiceRequest::create([
            'client_id' => $client->id,
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider, 'sanctum')->postJson("/api/requests/{$request->id}/refuse", [
            'message' => 'Too far for me',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('request_offers', [
            'service_request_id' => $request->id,
            'provider_id' => $provider->id,
            'status' => 'refused',
        ]);
        $this->assertEquals('pending', $request->fresh()->status);
    }

    public function test_second_talent_cannot_accept_already_accepted_mission()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider1 = User::factory()->create(['role' => 'provider', 'is_verified_student' => true]);
        $provider2 = User::factory()->create(['role' => 'provider', 'is_verified_student' => true]);
        $category = ServiceCategory::create(['name' => 'Plumbing']);
        $request = ServiceRequest::create([
            'client_id' => $client->id,
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
            'status' => 'pending',
        ]);

        // First talent accepts
        $this->actingAs($provider1, 'sanctum')->postJson("/api/requests/{$request->id}/offers");

        // Second talent tries to accept
        $response = $this->actingAs($provider2, 'sanctum')->postJson("/api/requests/{$request->id}/offers");

        $response->assertStatus(409);
        $response->assertJsonFragment(['message' => 'This mission has already been accepted by another talent']);
        $this->assertEquals($provider1->id, $request->fresh()->selected_provider_id);
    }

    public function test_public_provider_payload_does_not_expose_whatsapp()
    {
        User::factory()->create([
            'role' => 'provider',
            'is_verified_student' => true,
            'whatsapp_number' => '0600000002',
        ]);

        $response = $this->getJson('/api/providers');

        $response->assertStatus(200)
            ->assertJsonMissingPath('data.0.whatsapp_number');
    }

    public function test_authenticated_client_can_reveal_provider_whatsapp()
    {
        $client = User::factory()->create(['role' => 'client', 'whatsapp_number' => '0600000001']);
        $provider = User::factory()->create([
            'role' => 'provider',
            'is_verified_student' => true,
        ]);
        $category = ServiceCategory::create(['name' => 'Plumbing']);
        $serviceRequest = ServiceRequest::create([
            'client_id' => $client->id,
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
            'status' => 'in_progress',
            'selected_provider_id' => $provider->id,
        ]);

        // Selected provider can view client's whatsapp via client-contact
        $response = $this->actingAs($provider, 'sanctum')
            ->getJson("/api/requests/{$serviceRequest->id}/client-contact");

        $response->assertStatus(200)
            ->assertJsonPath('data.whatsapp_number', '0600000001');
    }

    public function test_guest_cannot_reveal_provider_whatsapp()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->create([
            'role' => 'provider',
            'is_verified_student' => true,
            'whatsapp_number' => '0600000002',
        ]);
        $category = ServiceCategory::create(['name' => 'Plumbing']);
        $serviceRequest = ServiceRequest::create([
            'client_id' => $client->id,
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'budget' => 200,
            'status' => 'in_progress',
            'selected_provider_id' => $provider->id,
        ]);

        // Unauthenticated user cannot access client-contact
        $response = $this->getJson("/api/requests/{$serviceRequest->id}/client-contact");

        $response->assertStatus(401);
    }
}
