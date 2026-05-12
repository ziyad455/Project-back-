<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            // Mapping is_verified to is_verified_student for providers
            $query->where('is_verified_student', $request->is_verified == '1');
        }

        // Search logic similar to providers method
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

        return response()->json($users);
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

        return response()->json($query->get());
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

        return response()->json($user);
    }

    /**
     * Reveal a provider's WhatsApp number to authenticated clients only.
     */
    public function contact(Request $request, $id)
    {
        if ($request->user()->role !== 'client') {
            return response()->json(['message' => 'Only authenticated clients can contact talents'], 403);
        }

        $provider = User::where('id', $id)
            ->where('role', 'provider')
            ->where('is_verified_student', true)
            ->select(['id', 'first_name', 'last_name', 'whatsapp_number'])
            ->firstOrFail();

        return response()->json([
            'provider_id' => $provider->id,
            'name' => trim($provider->first_name.' '.$provider->last_name),
            'whatsapp_number' => $provider->whatsapp_number,
        ]);
    }

    /**
     * Get the authenticated user's own full profile.
     */
    public function myProfile()
    {
        return response()->json(Auth::user());
    }

    /**
     * Return summary metrics for the authenticated talent dashboard.
     */
    public function talentStats(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'provider') {
            return response()->json(['message' => 'Only talents can view talent stats'], 403);
        }

        $openRequestsQuery = ServiceRequest::where('status', 'pending');

        if ($user->is_verified_student && $user->city) {
            $openRequestsQuery->where('city', $user->city);
        }

        return response()->json([
            'completed_jobs' => $user->completed_jobs ?? 0,
            'average_rating' => $user->average_rating ?? 0,
            'total_votes' => $user->total_votes ?? 0,
            'open_requests' => $user->is_verified_student ? $openRequestsQuery->count() : 0,
            'submitted_offers' => $user->offers()->count(),
            'accepted_offers' => $user->offers()->where('status', 'accepted')->count(),
            'is_verified_student' => (bool) $user->is_verified_student,
        ]);
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

        return response()->json(['message' => 'Profil mis à jour avec succès', 'user' => $user]);
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

        return response()->json(['message' => 'Numéro ajouté avec succès', 'user' => $user]);
    }

    /**
     * Update the authenticated user's avatar.
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Photo de profil mise à jour',
            'avatar_url' => $user->avatar_url,
            'user' => $user
        ]);
    }
}
