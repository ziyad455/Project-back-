<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Submit a review for a provider.
     */
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        // Only the client of the request can leave a review
        if ($serviceRequest->client_id !== Auth::id() && $serviceRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only completed or in_progress requests can be reviewed
        if (!in_array($serviceRequest->status, ['completed', 'in_progress', 'provider_selected'])) {
            return response()->json(['message' => 'This request cannot be reviewed yet'], 400);
        }

        $providerId = $serviceRequest->selected_provider_id;
        if (!$providerId) {
            return response()->json(['message' => 'No provider associated with this request'], 400);
        }

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated, $serviceRequest, $providerId) {
            // Create the review
            $review = Review::create([
                'service_request_id' => $serviceRequest->id,
                'client_id'          => Auth::id(),
                'provider_id'        => $providerId,
                'rating'             => $validated['rating'],
                'comment'            => $validated['comment'],
            ]);

            // Mark request as completed if it wasn't already
            $serviceRequest->update(['status' => 'completed']);

            // Update Provider stats
            $provider = User::find($providerId);
            $stats = Review::where('provider_id', $providerId)
                ->selectRaw('COUNT(*) as total_votes, AVG(rating) as average_rating')
                ->first();

            $provider->update([
                'total_votes'    => $stats->total_votes,
                'average_rating' => round($stats->average_rating, 1),
                'completed_jobs' => $provider->completed_jobs + 1
            ]);

            return response()->json([
                'message' => 'Merci pour votre avis !',
                'review'  => $review,
                'provider_stats' => [
                    'rating' => $provider->average_rating,
                    'votes'  => $provider->total_votes
                ]
            ]);
        });
    }

    /**
     * Get reviews for a specific provider.
     */
    public function index($providerId)
    {
        $reviews = Review::where('provider_id', $providerId)
            ->with('client:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reviews);
    }
}
