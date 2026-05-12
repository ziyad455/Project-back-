<?php

namespace App\Http\Controllers;

use App\Models\RequestOffer;
use App\Models\ServiceRequest;
use App\Notifications\MissionAcceptedNotification;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestOfferController extends Controller
{
    /**
     * List missions accepted (or interacted with) by the authenticated talent.
     */
    public function myOffers()
    {
        $user = Auth::user();

        if ($user->role !== 'provider') {
            return ApiResponse::error('Only talents can view their missions', 403);
        }

        $offers = $user->offers()
            ->with(['serviceRequest.category', 'serviceRequest.client'])
            ->latest()
            ->get();

        return ApiResponse::success($offers->toArray(), 'My offers retrieved');
    }

    /**
     * Talent accepts a mission (first-come-first-served).
     */
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'provider') {
            return ApiResponse::error('Only providers can accept missions', 403);
        }

        if (! $user->is_verified_student) {
            return ApiResponse::error('Only verified talents can accept missions', 403);
        }

        if ($serviceRequest->client_id === $user->id) {
            return ApiResponse::error('You cannot accept your own mission', 400);
        }

        $validated = $request->validate([
            'offered_price' => 'nullable|numeric|min:0',
            'message' => 'nullable|string|max:1000',
        ]);

        try {
            $offer = DB::transaction(function () use ($serviceRequest, $user, $validated) {
                $lockedRequest = ServiceRequest::lockForUpdate()->findOrFail($serviceRequest->id);

                if (! in_array($lockedRequest->status, ['pending', 'open'], true)) {
                    throw new \RuntimeException('This mission has already been accepted by another talent');
                }

                $lockedRequest->update([
                    'status' => 'in_progress',
                    'selected_provider_id' => $user->id,
                ]);

                $offer = $lockedRequest->offers()->create([
                    'provider_id' => $user->id,
                    'offered_price' => $validated['offered_price'] ?? null,
                    'message' => $validated['message'] ?? null,
                    'status' => 'accepted',
                ]);

                $lockedRequest->offers()
                    ->where('id', '!=', $offer->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'rejected']);

                return $offer;
            });
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }

        // Notify the client (mission owner) if they are a registered user
        $client = $serviceRequest->client;
        if ($client) {
            $client->notify(new MissionAcceptedNotification($serviceRequest, $user));
        }

        return ApiResponse::success(
            $offer->load('serviceRequest')->toArray(),
            'Mission accepted successfully',
            201
        );
    }

    /**
     * Talent explicitly declines a mission.
     */
    public function refuse(Request $request, ServiceRequest $serviceRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'provider') {
            return ApiResponse::error('Only providers can refuse missions', 403);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        $offer = $serviceRequest->offers()->create([
            'provider_id' => $user->id,
            'offered_price' => null,
            'message' => $validated['message'] ?? null,
            'status' => 'refused',
        ]);

        return ApiResponse::success($offer->toArray(), 'Mission declined successfully');
    }
}
