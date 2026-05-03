<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Client leaves a review for a provider after a completed service.
     */
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        if (Auth::id() !== $serviceRequest->client_id) {
            return response()->json(['message' => 'Only the client can leave a review'], 403);
        }

        if ($serviceRequest->status !== 'completed') {
            return response()->json(['message' => 'Reviews can only be left for completed requests'], 400);
        }

        if (!$serviceRequest->selected_provider_id) {
            return response()->json(['message' => 'No provider was selected for this request'], 400);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $review = Review::updateOrCreate(
            ['service_request_id' => $serviceRequest->id],
            [
                'reviewer_id' => Auth::id(),
                'provider_id' => $serviceRequest->selected_provider_id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]
        );

        // Update provider rating (this is a simplified version)
        $provider = $serviceRequest->selectedProvider;
        $avg = Review::where('provider_id', $provider->id)->avg('rating');
        $count = Review::where('provider_id', $provider->id)->count();
        
        $provider->update([
            'average_rating' => $avg,
            'total_votes' => $count
        ]);

        return response()->json($review, 201);
    }
}
