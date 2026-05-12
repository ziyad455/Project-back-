<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\ApiResponse;
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
        if ($serviceRequest->client_id !== Auth::id()) {
            return ApiResponse::error('Unauthorized', 403);
        }

        // Only completed missions can be reviewed
        if ($serviceRequest->status !== 'completed') {
            return ApiResponse::error('You can only leave a review after the mission is completed', 400);
        }

        $providerId = $serviceRequest->selected_provider_id;
        if (!$providerId) {
            return ApiResponse::error('No provider associated with this request', 400);
        }

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated, $serviceRequest, $providerId) {
            $review = Review::create([
                'service_request_id' => $serviceRequest->id,
                'reviewer_id'        => Auth::id(),
                'provider_id'        => $providerId,
                'rating'             => $validated['rating'],
                'comment'            => $validated['comment'],
            ]);

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

            return ApiResponse::success([
                'review' => $review,
                'provider_stats' => [
                    'rating' => $provider->average_rating,
                    'votes'  => $provider->total_votes
                ],
            ], 'Merci pour votre avis !');
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

        return ApiResponse::success($reviews->toArray(), 'Reviews retrieved');
    }
}
