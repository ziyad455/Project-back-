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

    public function test_provider_can_place_offer()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->create(['role' => 'provider']);
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

    public function test_client_can_accept_offer()
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->create(['role' => 'provider']);
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
        $this->assertEquals('provider_selected', $request->fresh()->status);
        $this->assertEquals($provider->id, $request->fresh()->selected_provider_id);
    }
}
