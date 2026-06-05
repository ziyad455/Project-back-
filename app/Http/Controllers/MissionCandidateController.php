<?php

namespace App\Http\Controllers;

use App\Models\MissionCandidate;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\TalentAppliedNotification;
use App\Notifications\TalentChosenNotification;
use App\Notifications\TalentNotChosenNotification;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MissionCandidateController extends Controller
{
    /**
     * Talent applies to a mission.
     */
    public function apply(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        if ($user->role !== 'provider') {
            return ApiResponse::error('Only talents can apply to missions', 403);
        }

        if (!$user->is_verified_student) {
            return ApiResponse::error('Only verified talents can apply to missions', 403);
        }

        if ($serviceRequest->status !== 'open') {
            return ApiResponse::error('This mission is not accepting applications', 400);
        }

        if ($serviceRequest->client_id === $user->id) {
            return ApiResponse::error('You cannot apply to your own mission', 400);
        }

        $alreadyApplied = MissionCandidate::where('mission_id', $serviceRequest->id)
            ->where('talent_id', $user->id)
            ->exists();

        if ($alreadyApplied) {
            return ApiResponse::error('You have already applied to this mission', 409);
        }

        $activeApplications = MissionCandidate::where('talent_id', $user->id)
            ->where('status', 'pending')
            ->count();

        if ($activeApplications >= 10) {
            return ApiResponse::error('Maximum active applications reached (10)', 400);
        }

        $candidate = MissionCandidate::create([
            'mission_id' => $serviceRequest->id,
            'talent_id' => $user->id,
            'status' => 'pending',
        ]);

        $candidate->load('mission.translations', 'mission.category.translations');

        // Notify the client that a talent applied
        $client = $serviceRequest->client;
        if ($client) {
            $client->notify(new TalentAppliedNotification($serviceRequest, $user, app()->getLocale()));
        }

        return ApiResponse::success($candidate->toArray(), 'Application submitted successfully', 201);
    }

    /**
     * Talent withdraws their application.
     */
    public function withdraw(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        $candidate = MissionCandidate::where('mission_id', $serviceRequest->id)
            ->where('talent_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $candidate->delete();

        return ApiResponse::success([], 'Application withdrawn successfully');
    }

    /**
     * Client views all candidates for their mission.
     */
    public function candidates(Request $request, ServiceRequest $serviceRequest)
    {
        $candidates = MissionCandidate::where('mission_id', $serviceRequest->id)
            ->with(['talent.translations', 'talent.reviewsReceived'])
            ->latest()
            ->get()
            ->map(function ($candidate) {
                $talent = $candidate->talent;
                $reviews = $talent->reviewsReceived ?? collect();
                $avgRating = $reviews->avg('rating');
                $completedJobs = ServiceRequest::where('selected_provider_id', $talent->id)
                    ->where('status', 'completed')
                    ->count();

                return [
                    'id' => $candidate->id,
                    'status' => $candidate->status,
                    'created_at' => $candidate->created_at,
                    'talent' => [
                        'id' => $talent->id,
                        'first_name' => $talent->first_name,
                        'last_name' => $talent->last_name,
                        'avatar_url' => $talent->avatar_url,
                        'city' => $talent->city,
                        'skills' => $talent->skills,
                        'average_rating' => $avgRating ? round($avgRating, 1) : 0,
                        'completed_jobs' => $completedJobs,
                    ],
                ];
            });

        return ApiResponse::success($candidates->toArray(), 'Candidates retrieved');
    }

    /**
     * Client chooses a talent for their mission.
     */
    public function choose(Request $request, ServiceRequest $serviceRequest, User $talent)
    {
        $user = $request->user();

        if ($user && $user->id !== $serviceRequest->client_id && !$user->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
        }

        if ($serviceRequest->status !== 'open') {
            return ApiResponse::error('This mission is not open for selection', 400);
        }

        $candidate = MissionCandidate::where('mission_id', $serviceRequest->id)
            ->where('talent_id', $talent->id)
            ->where('status', 'pending')
            ->first();

        if (!$candidate) {
            return ApiResponse::error('This talent has not applied to this mission', 404);
        }

        DB::transaction(function () use ($serviceRequest, $talent, $candidate) {
            $serviceRequest->update([
                'status' => 'in_progress',
                'selected_provider_id' => $talent->id,
            ]);

            $candidate->update(['status' => 'chosen']);

            MissionCandidate::where('mission_id', $serviceRequest->id)
                ->where('talent_id', '!=', $talent->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);
        });

        $serviceRequest->load('category.translations', 'translations');

        // Notify chosen talent
        $client = $serviceRequest->client;
        if ($client) {
            $talent->notify(new TalentChosenNotification($serviceRequest, $client, app()->getLocale()));
        }

        // Notify rejected candidates
        $rejectedCandidates = MissionCandidate::where('mission_id', $serviceRequest->id)
            ->where('status', 'rejected')
            ->with('talent')
            ->get();

        foreach ($rejectedCandidates as $rejected) {
            $rejected->talent->notify(new TalentNotChosenNotification($serviceRequest, app()->getLocale()));
        }

        $sr = $serviceRequest->fresh();
        $sr->load('category.translations', 'translations');

        return ApiResponse::success($sr->toArray(), 'Talent selected successfully');
    }
}
