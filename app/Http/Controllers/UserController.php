<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ServiceRequest;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * List users with filters (role, verification status).
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('is_verified')) {
            $query->where('is_verified_student', $request->is_verified == '1');
        }

        if ($request->has('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('title', 'like', $term)
                  ->orWhere('bio', 'like', $term);
            });
        }

        $users = $query->select([
                'id', 'first_name', 'last_name', 'title', 'city', 'avatar',
                'bio', 'skills', 'average_rating', 'total_votes'
            ])
            ->orderByDesc('average_rating')
            ->get();

        return ApiResponse::success($users->toArray(), 'Users retrieved');
    }

    /**
     * List all verified providers (for the Talents discovery page).
     */
    public function providers(Request $request)
    {
        $query = User::where('role', 'provider')
            ->where('is_verified_student', true)
            ->select([
                'id', 'first_name', 'last_name', 'title', 'city', 'avatar',
                'bio', 'skills', 'hourly_rate', 'average_rating', 'is_verified_student',
                'total_votes', 'completed_jobs', 'job_success_rate',
                'portfolio_url', 'university', 'field_of_study'
            ])
            ->orderByDesc('average_rating')
            ->orderByDesc('total_votes');

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        if ($request->has('category')) {
            $query->whereJsonContains('skills', $request->category);
        }

        if ($request->has('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('title', 'like', $term)
                  ->orWhere('bio', 'like', $term);
            });
        }

        return ApiResponse::success($query->get()->toArray(), 'Providers retrieved');
    }

    /**
     * Show a single public provider profile by ID.
     */
    public function show($id)
    {
        $user = User::where('id', $id)
            ->where('role', 'provider')
            ->where('is_verified_student', true)
            ->select([
                'id', 'first_name', 'last_name', 'title', 'city', 'avatar',
                'bio', 'skills', 'hourly_rate', 'average_rating', 'is_verified_student',
                'total_votes', 'completed_jobs', 'job_success_rate',
                'portfolio_url', 'university', 'field_of_study'
            ])
            ->firstOrFail();

        return ApiResponse::success($user->toArray(), 'Provider retrieved');
    }

    /**
     * DEPRECATED: Direct provider contact has been removed.
     * Talents now contact clients via the mission-based WhatsApp flow.
     * @see ServiceRequestController::clientContact()
     */
    public function contact(Request $request, $id)
    {
        return ApiResponse::error('Direct contact is not available. Accept a mission to contact the client.', 403);
    }

    /**
     * Get the authenticated user's own full profile.
     */
    public function myProfile()
    {
        return ApiResponse::success(
            Auth::user()->toArray(),
            'Profile retrieved'
        );
    }

    /**
     * Return summary metrics for the authenticated talent dashboard.
     */
    public function talentStats(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'provider') {
            return ApiResponse::error('Only talents can view talent stats', 403);
        }

        $openRequestsQuery = ServiceRequest::where('status', 'pending');

        if ($user->is_verified_student && $user->city) {
            $openRequestsQuery->where('city', $user->city);
        }

        return ApiResponse::success([
            'completed_jobs' => $user->completed_jobs ?? 0,
            'average_rating' => $user->average_rating ?? 0,
            'total_votes' => $user->total_votes ?? 0,
            'open_requests' => $user->is_verified_student ? $openRequestsQuery->count() : 0,
            'submitted_offers' => $user->offers()->count(),
            'accepted_offers' => $user->offers()->where('status', 'accepted')->count(),
            'is_verified_student' => (bool) $user->is_verified_student,
        ], 'Talent stats retrieved');
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name'      => 'sometimes|string|max:100',
            'last_name'       => 'sometimes|string|max:100',
            'city'            => 'sometimes|string|max:100',
            'whatsapp_number' => 'sometimes|string|max:20',
            'title'           => 'sometimes|string|max:255',
            'bio'             => 'sometimes|string',
            'portfolio_url'   => 'sometimes|url|nullable',
            'skills'          => 'sometimes|array',
            'hourly_rate'     => 'sometimes|numeric|min:0',
            'university'      => 'sometimes|string|max:255',
            'field_of_study'  => 'sometimes|string|max:255',
        ]);

        $user->update($validated);

        return ApiResponse::success(
            $user->toArray(),
            'Profil mis à jour avec succès'
        );
    }

    /**
     * Update the authenticated user's phone number.
     */
    public function updatePhone(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'whatsapp_number' => 'required|string|max:20',
        ]);

        $user->update(['whatsapp_number' => $validated['whatsapp_number']]);

        return ApiResponse::success(
            $user->toArray(),
            'Numéro ajouté avec succès'
        );
    }
}
