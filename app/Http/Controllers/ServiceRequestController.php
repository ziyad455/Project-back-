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
        $user = $request->user();

        if ($user->role !== 'provider' || ! $user->is_verified_student) {
            return response()->json(['message' => 'Only verified talents can view service requests'], 403);
        }

        $query = ServiceRequest::whereIn('status', ['pending', 'open'])->with('category', 'client');

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        if ($request->has('category_id') || $request->has('service_category_id')) {
            $ids = explode(',', $request->category_id ?? $request->service_category_id);
            $query->where(function($q) use ($ids) {
                $q->whereIn('service_category_id', $ids)
                  ->orWhereIn('category_id', $ids);
            });
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
        $user = $request->user('sanctum') ?? $request->user();

        if ($user && $user->role !== 'client') {
            return response()->json(['message' => 'Only clients can create service requests'], 403);
        }

        $validated = $request->validate([
            'city'                => 'required|string',
            'service_category_id' => 'required|exists:service_categories,id',
            'description'         => 'required|string',
            'proposed_price'      => 'required|numeric|min:0',
            'guest_name'          => [$user ? 'nullable' : 'required', 'string', 'max:255'],
            'guest_email'         => ['nullable', 'email', 'max:255'],
            'guest_whatsapp_number' => [$user ? 'nullable' : 'required', 'string', 'max:20'],
        ]);

        if ($user) {
            $validated['client_id'] = $user->id;
            unset($validated['guest_name'], $validated['guest_email'], $validated['guest_whatsapp_number']);
        }

        $serviceRequest = ServiceRequest::create($validated);
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

        // Tier 2: The rest — notified after 5 minutes if request still pending
        $tier1Ids = $tier1->pluck('id')->toArray();
        if ($providers->count() > 10) {
            NotifyRemainingProviders::dispatch($serviceRequest, $tier1Ids)
                ->delay(now()->addMinutes(5));
        }
        // ──────────────────────────────────────────────────────────────────────

        return response()->json($serviceRequest, 201);
    }

    /**
     * Display the specified request with its offers.
     */
    public function show(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();
        $isOwner = ($serviceRequest->client_id !== null && $user->id === $serviceRequest->client_id)
            || ($serviceRequest->user_id !== null && $user->id === $serviceRequest->user_id);
        $isSelectedProvider = $user->id === $serviceRequest->selected_provider_id;
        $hasOwnOffer = $serviceRequest->offers()->where('provider_id', $user->id)->exists();
        $canProviderView = $user->role === 'provider'
            && $user->is_verified_student
            && (in_array($serviceRequest->status, ['pending', 'open'], true) || $isSelectedProvider || $hasOwnOffer);

        if (! $user->is_admin && ! $isOwner && ! $canProviderView) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($canProviderView && ! $isOwner && ! $user->is_admin) {
            return response()->json($serviceRequest->load([
                'category',
                'client',
                'selectedProvider',
                'review',
                'offers' => fn ($query) => $query->where('provider_id', $user->id)->with('provider'),
            ]));
        }

        return response()->json($serviceRequest->load(['category', 'client', 'offers.provider', 'selectedProvider', 'review']));
    }

    /**
     * List requests created by the authenticated client.
     */
    public function myRequests()
    {
        if (Auth::user()->role !== 'client') {
            return response()->json(['message' => 'Only clients can view their service requests'], 403);
        }

        return response()->json(
            Auth::user()->serviceRequests()->with(['category', 'offers.provider', 'selectedProvider'])->latest()->get()
        );
    }

    /**
     * Mark a request as completed.
     */
    public function complete(ServiceRequest $serviceRequest)
    {
        $userId = Auth::id();
        $isOwner = $userId === $serviceRequest->client_id || $userId === $serviceRequest->user_id;
        $isSelectedProvider = $userId === $serviceRequest->selected_provider_id;

        if (!$isOwner && !$isSelectedProvider) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $serviceRequest->update(['status' => 'completed']);

        return response()->json(['message' => 'Mission marked as completed', 'request' => $serviceRequest]);
    }

    /**
     * Store a mission posted by anyone (public).
     */
    public function storePublic(Request $request)
    {
        $validated = $request->validate([
            'client_name'  => 'required|string',
            'client_email' => 'required|email',
            'client_phone' => 'required|string',
            'title'        => 'required|string',
            'category_id'  => 'required|exists:service_categories,id',
            'description'  => 'required|string',
            'budget'       => 'required|numeric|min:0',
            'city'         => 'required|string',
            'deadline'     => 'nullable|date|after_or_equal:today',
        ]);

        // 1. User Logic: Find or Create
        $user = User::where('email', $validated['client_email'])->first();

        if (!$user) {
            // Split name into first/last name
            $nameParts = explode(' ', $validated['client_name'], 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

            $user = User::create([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $validated['client_email'],
                'password'   => bcrypt(str()->random(16)),
                'role'       => 'client',
                'whatsapp_number' => $validated['client_phone'],
                'city'       => $validated['city'],
            ]);
        }

        // 2. Create Mission
        $serviceRequest = ServiceRequest::create([
            'client_id'           => $user->id,
            'user_id'             => $user->id, // keeping both for compatibility
            'client_name'         => $validated['client_name'],
            'client_email'        => $validated['client_email'],
            'client_phone'        => $validated['client_phone'],
            'title'               => $validated['title'],
            'service_category_id' => $validated['category_id'],
            'category_id'         => $validated['category_id'],
            'description'         => $validated['description'],
            'budget'              => $validated['budget'],
            'city'                => $validated['city'],
            'deadline'            => $validated['deadline'] ?? null,
            'status'              => 'open',
        ]);

        $serviceRequest->load('category');

        // ── Tiered Notification Logic by Category ──────────────────────────
        // Get all verified providers in this category
        $providers = User::where('role', 'provider')
            ->where('is_verified_student', true)
            ->whereHas('categories', function($q) use ($serviceRequest) {
                $q->where('service_categories.id', $serviceRequest->category_id);
            })
            ->orderByDesc('average_rating')
            ->orderByDesc('total_votes')
            ->get();

        // Tier 1: Top 10 — notified immediately
        $tier1 = $providers->take(10);
        foreach ($tier1 as $provider) {
            $provider->notify(new NewServiceRequestNotification($serviceRequest, 'top'));
        }

        // Tier 2: The rest — notified after 5 minutes
        $tier1Ids = $tier1->pluck('id')->toArray();
        if ($providers->count() > 10) {
            NotifyRemainingProviders::dispatch($serviceRequest, $tier1Ids)
                ->delay(now()->addMinutes(5));
        }
        // ──────────────────────────────────────────────────────────────────

        return response()->json([
            'message' => 'Mission postée avec succès !',
            'request' => $serviceRequest
        ], 201);
    }

    /**
     * Get statistics for the provider dashboard.
     */
    public function getStats()
    {
        $provider = Auth::user();

        // 1. Available missions (open missions in city & matching provider's categories)
        // We get the provider's category IDs from the pivot table (provider_services)
        $providerCategoryIds = $provider->categories()->pluck('service_categories.id')->toArray();

        $availableMissionsCount = ServiceRequest::where('status', 'open')
            ->where('city', $provider->city)
            ->whereIn('service_category_id', $providerCategoryIds)
            ->count();

        // 2. Pending offers (offers by provider on pending/open requests)
        $pendingOffersCount = \App\Models\RequestOffer::where('provider_id', $provider->id)
            ->whereHas('serviceRequest', function($q) {
                $q->whereIn('status', ['pending', 'open']);
            })
            ->count();

        // 3. Completed missions (missions where this provider was selected and marked completed)
        $completedMissionsCount = ServiceRequest::where('status', 'completed')
            ->where('selected_provider_id', $provider->id)
            ->count();

        return response()->json([
            'available_missions' => $availableMissionsCount,
            'pending_offers'     => $pendingOffersCount,
            'completed_missions' => $completedMissionsCount,
            'rating'             => $provider->average_rating ?: 0,
        ]);
    }
}
