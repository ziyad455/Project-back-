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
            'proposed_price' => 200,
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
            'proposed_price' => 200,
        ]);

        $response->assertStatus(201);
        $response->assertJsonMissingPath('guest_whatsapp_number');

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
            'proposed_price' => 200,
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
            'proposed_price' => 200,
            'status' => 'pending',
        ]);

        $request->offers()->create([
            'provider_id' => $provider->id,
            'offered_price' => 250,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider, 'sanctum')->getJson('/api/offers/my');

        $response->assertStatus(200)
            ->assertJsonPath('0.provider_id', $provider->id)
            ->assertJsonPath('0.service_request.category.name', 'Plumbing');
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
            'proposed_price' => 200,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider, 'sanctum')->getJson('/api/talent/stats');

        $response->assertStatus(200)
            ->assertJsonPath('completed_jobs', 2)
            ->assertJsonPath('open_requests', 1)
            ->assertJsonPath('is_verified_student', true);
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
            'proposed_price' => 200,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider, 'sanctum')->postJson("/api/requests/{$request->id}/offers", [
            'offered_price' => 250,
        ]);

        $response->assertStatus(403);
    }

    public function test_client_can_accept_offer()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->create(['role' => 'provider', 'is_verified_student' => true]);
        $category = ServiceCategory::create(['name' => 'Plumbing']);
        $request = ServiceRequest::create([
            'client_id' => $client->id,
            'city' => 'Marrakech',
            'service_category_id' => $category->id,
            'description' => 'Fix my sink',
            'proposed_price' => 200,
            'status' => 'pending',
        ]);
        
        $offer = $request->offers()->create([
            'provider_id' => $provider->id,
            'offered_price' => 250,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($client, 'sanctum')->postJson("/api/offers/{$offer->id}/accept");

        $response->assertStatus(200);
        $this->assertEquals('accepted', $offer->fresh()->status);
        $this->assertEquals('in_progress', $request->fresh()->status);
        $this->assertEquals($provider->id, $request->fresh()->selected_provider_id);
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
            ->assertJsonMissingPath('0.whatsapp_number');
    }

    public function test_authenticated_client_can_reveal_provider_whatsapp()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->create([
            'role' => 'provider',
            'is_verified_student' => true,
            'whatsapp_number' => '0600000002',
        ]);

        $response = $this->actingAs($client, 'sanctum')->getJson("/api/providers/{$provider->id}/contact");

        $response->assertStatus(200)
            ->assertJsonPath('whatsapp_number', '0600000002');
    }

    public function test_guest_cannot_reveal_provider_whatsapp()
    {
        $provider = User::factory()->create([
            'role' => 'provider',
            'is_verified_student' => true,
            'whatsapp_number' => '0600000002',
        ]);

        $response = $this->getJson("/api/providers/{$provider->id}/contact");

        $response->assertStatus(401);
    }
}
