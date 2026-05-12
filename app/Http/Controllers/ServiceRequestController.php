<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyRemainingProviders;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\MissionCompletedNotification;
use App\Notifications\NewServiceRequestNotification;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use App\Notifications\WelcomeGuestNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceRequestController extends Controller
{
    /**
     * List all pending requests (accessible by providers).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'provider' || ! $user->is_verified_student) {
            return ApiResponse::error('Only verified talents can view service requests', 403);
        }

        $query = ServiceRequest::whereIn('status', ['pending', 'open'])->with('category', 'client');

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        if ($request->has('category_id') || $request->has('service_category_id')) {
            $ids = explode(',', $request->service_category_id ?? $request->category_id);
            $query->whereIn('service_category_id', $ids);
        }

        return ApiResponse::success($query->latest()->get()->toArray(), 'Service requests retrieved');
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
            return ApiResponse::error('Only clients can create service requests', 403);
        }

        $validated = $request->validate([
            'city'                => 'required|string',
            'service_category_id' => 'required|exists:service_categories,id',
            'description'         => 'required|string',
            'budget'              => 'nullable|numeric|min:0',
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
        $providers = User::where('role', 'provider')
            ->where('is_verified_student', true)
            ->where('city', $serviceRequest->city)
            ->orderByDesc('average_rating')
            ->orderByDesc('total_votes')
            ->get();

        $tier1 = $providers->take(10);
        foreach ($tier1 as $provider) {
            $provider->notify(new NewServiceRequestNotification($serviceRequest, 'top'));
        }

        $tier1Ids = $tier1->pluck('id')->toArray();
        if ($providers->count() > 10) {
            NotifyRemainingProviders::dispatch($serviceRequest, $tier1Ids)
                ->delay(now()->addMinutes(30));
        }
        // ──────────────────────────────────────────────────────────────────────

        return ApiResponse::success($serviceRequest->toArray(), 'Service request created', 201);
    }

    /**
     * Display the specified request with its offers.
     */
    public function show(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();
        $isOwner = $serviceRequest->client_id !== null && $user->id === $serviceRequest->client_id;
        $isSelectedProvider = $user->id === $serviceRequest->selected_provider_id;
        $hasOwnOffer = $serviceRequest->offers()->where('provider_id', $user->id)->exists();
        $canProviderView = $user->role === 'provider'
            && $user->is_verified_student
            && (in_array($serviceRequest->status, ['pending', 'open'], true) || $isSelectedProvider || $hasOwnOffer);

        if (! $user->is_admin && ! $isOwner && ! $canProviderView) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $loadRelations = ['category', 'client', 'selectedProvider', 'review'];

        if ($canProviderView && ! $isOwner && ! $user->is_admin) {
            $serviceRequest->load(array_merge($loadRelations, [
                'offers' => fn ($query) => $query->where('provider_id', $user->id)->with('provider'),
            ]));
        } else {
            $serviceRequest->load(array_merge($loadRelations, ['offers.provider']));
        }

        $responseData = $serviceRequest->toArray();



        return ApiResponse::success($responseData, 'Service request retrieved');
    }

    /**
     * Get the client's WhatsApp contact info for a service request.
     * Only the selected provider can access this, and only when status is in_progress.
     */
    public function clientContact(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        if ($user->id !== $serviceRequest->selected_provider_id) {
            return ApiResponse::error("Only the assigned talent can view the client's contact information", 403);
        }

        if ($serviceRequest->status !== 'in_progress') {
            return ApiResponse::error('Contact information is only available for accepted missions', 403);
        }

        $whatsappInfo = $this->resolveClientWhatsApp($serviceRequest);

        return ApiResponse::success([
            'client_name'     => $whatsappInfo['client_name'],
            'whatsapp_number' => $whatsappInfo['whatsapp_number'],
            'whatsapp_link'   => $whatsappInfo['whatsapp_link'],
        ], 'Client contact retrieved');
    }

    /**
     * Resolve the client's WhatsApp number and build the WhatsApp link.
     * Handles both registered clients and guest clients.
     */
    private function resolveClientWhatsApp(ServiceRequest $serviceRequest): array
    {
        $title = $serviceRequest->title ?? 'Mission';

        if ($serviceRequest->client_id && $serviceRequest->client) {
            $clientName = trim($serviceRequest->client->first_name . ' ' . $serviceRequest->client->last_name);
            $whatsappNumber = $serviceRequest->client->whatsapp_number;
        } else {
            $clientName = $serviceRequest->guest_name
                ?? $serviceRequest->client_name
                ?? 'Client';
            $whatsappNumber = $serviceRequest->guest_whatsapp_number
                ?? $serviceRequest->client_phone
                ?? null;
        }

        $encodedTitle = urlencode($title);
        $whatsappLink = $whatsappNumber
            ? "https://wa.me/{$whatsappNumber}?text=Bonjour,%20je%20vous%20contacte%20via%20AjiKhdam%20pour%20la%20mission:%20{$encodedTitle}"
            : null;

        return [
            'client_name'     => $clientName,
            'whatsapp_number' => $whatsappNumber,
            'whatsapp_link'   => $whatsappLink,
        ];
    }

    /**
     * List requests created by the authenticated client.
     */
    public function myRequests()
    {
        if (Auth::user()->role !== 'client') {
            return ApiResponse::error('Only clients can view their service requests', 403);
        }

        $requests = Auth::user()->serviceRequests()
            ->with(['category', 'offers.provider', 'selectedProvider'])
            ->latest()
            ->get();

        return ApiResponse::success($requests->toArray(), 'My requests retrieved');
    }

    /**
     * Mark a request as completed.
     * Only the selected provider can mark a mission as completed.
     */
    public function complete(ServiceRequest $serviceRequest)
    {
        $userId = Auth::id();

        if ($userId !== $serviceRequest->selected_provider_id) {
            return ApiResponse::error('Only the assigned talent can mark this mission as completed', 403);
        }

        if ($serviceRequest->status !== 'in_progress') {
            return ApiResponse::error('Mission must be in progress to be marked as completed', 400);
        }

        return DB::transaction(function () use ($serviceRequest) {
            $serviceRequest->update(['status' => 'completed']);

            // Notify the client/owner
            $client = $serviceRequest->client;
            if ($client) {
                $client->notify(new MissionCompletedNotification($serviceRequest));
            }

            return ApiResponse::success(
                $serviceRequest->fresh()->toArray(),
                'Mission marked as completed'
            );
        });
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
            'service_category_id' => 'required|exists:service_categories,id',
            'description'  => 'required|string',
            'budget'       => 'nullable|numeric|min:0',
            'city'         => 'required|string',
            'deadline'     => 'nullable|date|after_or_equal:today',
        ]);

        // 1. User Logic: Find or Create
        $user = User::where('email', $validated['client_email'])->first();

        if (!$user) {
            $nameParts = explode(' ', $validated['client_name'], 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

            $tempPassword = str()->random(10);
            $user = User::create([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $validated['client_email'],
                'password'   => bcrypt($tempPassword),
                'role'       => 'client',
                'whatsapp_number' => $validated['client_phone'],
                'city'       => $validated['city'],
            ]);

            // Notify the user about their account and password
            $user->notify(new WelcomeGuestNotification($tempPassword));
        }

        // 2. Create Mission — uses canonical fields; boot() will sync legacy ones
        $serviceRequest = ServiceRequest::create([
            'client_id'           => $user->id,
            'client_name'         => $validated['client_name'],
            'client_email'        => $validated['client_email'],
            'client_phone'        => $validated['client_phone'],
            'title'               => $validated['title'],
            'service_category_id' => $validated['service_category_id'],
            'description'         => $validated['description'],
            'budget'              => $validated['budget'],
            'city'                => $validated['city'],
            'deadline'            => $validated['deadline'] ?? null,
            'status'              => 'open',
        ]);

        $serviceRequest->load('category');

        // ── Tiered Notification Logic by Category ──────────────────────────
        $providers = User::where('role', 'provider')
            ->where('is_verified_student', true)
            ->whereHas('categories', function($q) use ($serviceRequest) {
                $q->where('service_categories.id', $serviceRequest->service_category_id);
            })
            ->orderByDesc('average_rating')
            ->orderByDesc('total_votes')
            ->get();

        $tier1 = $providers->take(10);
        foreach ($tier1 as $provider) {
            $provider->notify(new NewServiceRequestNotification($serviceRequest, 'top'));
        }

        $tier1Ids = $tier1->pluck('id')->toArray();
        if ($providers->count() > 10) {
            NotifyRemainingProviders::dispatch($serviceRequest, $tier1Ids)
                ->delay(now()->addMinutes(30));
        }
        // ──────────────────────────────────────────────────────────────────

        return ApiResponse::success($serviceRequest->toArray(), 'Mission postée avec succès !', 201);
    }

    /**
     * Get statistics for the provider dashboard.
     */
    public function getStats()
    {
        $provider = Auth::user();

        $providerCategoryIds = $provider->categories()->pluck('service_categories.id')->toArray();

        $availableMissionsCount = ServiceRequest::where('status', 'open')
            ->where('city', $provider->city)
            ->whereIn('service_category_id', $providerCategoryIds)
            ->count();

        $pendingOffersCount = \App\Models\RequestOffer::where('provider_id', $provider->id)
            ->whereHas('serviceRequest', function($q) {
                $q->whereIn('status', ['pending', 'open']);
            })
            ->count();

        $completedMissionsCount = ServiceRequest::where('status', 'completed')
            ->where('selected_provider_id', $provider->id)
            ->count();

        return ApiResponse::success([
            'available_missions' => $availableMissionsCount,
            'pending_offers'     => $pendingOffersCount,
            'completed_missions' => $completedMissionsCount,
            'rating'             => $provider->average_rating ?: 0,
        ], 'Provider stats retrieved');
    }
}
