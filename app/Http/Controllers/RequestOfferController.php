<?php

namespace App\Http\Controllers;

use App\Models\RequestOffer;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestOfferController extends Controller
{
    /**
     * Provider places an offer on a request.
     */
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        if (Auth::user()->role !== 'provider') {
            return response()->json(['message' => 'Only providers can place offers'], 403);
        }

        if ($serviceRequest->status !== 'pending') {
            return response()->json(['message' => 'This request is no longer accepting offers'], 400);
        }

        $validated = $request->validate([
            'offered_price' => 'required|numeric|min:0',
            'message' => 'nullable|string|max:1000',
        ]);

        $offer = $serviceRequest->offers()->updateOrCreate(
            ['provider_id' => Auth::id()],
            [
                'offered_price' => $validated['offered_price'],
                'message' => $validated['message'] ?? null,
                'status' => 'pending'
            ]
        );

        return response()->json($offer, 201);
    }

    /**
     * Client accepts an offer.
     */
    public function accept(RequestOffer $requestOffer)
    {
        $serviceRequest = $requestOffer->serviceRequest;

        if (Auth::id() !== $serviceRequest->client_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Mark this offer as accepted
        $requestOffer->update(['status' => 'accepted']);

        // Mark other offers as rejected
        $serviceRequest->offers()->where('id', '!=', $requestOffer->id)->update(['status' => 'rejected']);

        // Update the request status and set the provider
        $serviceRequest->update([
            'status' => 'provider_selected',
            'selected_provider_id' => $requestOffer->provider_id
        ]);

        return response()->json(['message' => 'Offer accepted successfully', 'request' => $serviceRequest->load('selectedProvider')]);
    }
}
