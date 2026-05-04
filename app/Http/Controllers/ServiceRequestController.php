<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyRemainingProviders;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\NewServiceRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceRequestController extends Controller
{
    /**
     * List all pending requests (accessible by providers).
     */
    public function index(Request $request)
    {
        $query = ServiceRequest::where('status', 'pending')->with('category', 'client');

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        if ($request->has('service_category_id')) {
            $query->where('service_category_id', $request->service_category_id);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Store a new service request (Client only).
     * Implements the tiered notification system:
     *   - Tier 1: Top 10 providers in city (by rating) are notified immediately
     *   - Tier 2: Remaining providers are notified after 30 minutes if request still pending
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'city'                => 'required|string',
            'service_category_id' => 'required|exists:service_categories,id',
            'description'         => 'required|string',
            'proposed_price'      => 'required|numeric|min:0',
        ]);

        $serviceRequest = Auth::user()->serviceRequests()->create($validated);
        $serviceRequest->load('category', 'client');

        // ── Tiered Notification Logic ──────────────────────────────────────────
        // Get all verified providers in the same city, sorted by rating DESC
        $providers = User::where('role', 'provider')
            ->where('is_verified_student', true)
            ->where('city', $serviceRequest->city)
            ->orderByDesc('average_rating')
            ->orderByDesc('total_votes')
            ->get();

        // Tier 1: Top 10 — notified immediately
        $tier1 = $providers->take(10);
        foreach ($tier1 as $provider) {
            $provider->notify(new NewServiceRequestNotification($serviceRequest, 'top'));
        }

        // Tier 2: The rest — notified after 30 minutes if request still pending
        $tier1Ids = $tier1->pluck('id')->toArray();
        if ($providers->count() > 10) {
            NotifyRemainingProviders::dispatch($serviceRequest, $tier1Ids)
                ->delay(now()->addMinutes(30));
        }
        // ──────────────────────────────────────────────────────────────────────

        return response()->json($serviceRequest, 201);
    }

    /**
     * Display the specified request with its offers.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        return response()->json($serviceRequest->load(['category', 'client', 'offers.provider']));
    }

    /**
     * List requests created by the authenticated client.
     */
    public function myRequests()
    {
        return response()->json(
            Auth::user()->serviceRequests()->with(['category', 'offers.provider', 'selectedProvider'])->latest()->get()
        );
    }

    /**
     * Mark a request as completed.
     */
    public function complete(ServiceRequest $serviceRequest)
    {
        if (Auth::id() !== $serviceRequest->client_id && Auth::id() !== $serviceRequest->selected_provider_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $serviceRequest->update(['status' => 'completed']);

        return response()->json(['message' => 'Request marked as completed', 'request' => $serviceRequest]);
    }
}
