<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function pendingCount()
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $count = User::where('role', 'provider')
            ->where('is_verified_student', false)
            ->where(function($q) {
                $q->whereNotNull('document_id_card')
                  ->orWhereNotNull('document_student_proof');
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function allProviders()
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $providers = User::where('role', 'provider')
            ->select([
                'id', 'first_name', 'last_name', 'email', 'created_at',
                'university', 'field_of_study', 'is_verified_student',
                'document_id_card', 'document_student_proof'
            ])
            ->orderBy('is_verified_student', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($providers);
    }

    /**
     * Get a list of providers awaiting verification.
     */
    public function pendingProviders()
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $providers = User::where('role', 'provider')
            ->where('is_verified_student', false)
            ->where(function($q) {
                // Ideally they should have uploaded at least one document to be "pending review"
                $q->whereNotNull('document_id_card')
                  ->orWhereNotNull('document_student_proof');
            })
            ->select([
                'id', 'first_name', 'last_name', 'email', 'created_at',
                'university', 'field_of_study', 
                'document_id_card', 'document_student_proof'
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($providers);
    }

    /**
     * Show a provider verification document to an authenticated admin.
     */
    public function showProviderDocument(User $provider, string $document)
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($provider->role !== 'provider') {
            return response()->json(['message' => 'Document introuvable.'], 404);
        }

        $path = match ($document) {
            'id-card' => $provider->document_id_card,
            'student-proof' => $provider->document_student_proof,
            default => null,
        };

        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Document introuvable.'], 404);
        }

        $disk = Storage::disk('public');
        $filename = basename($path);

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Approve a provider's verification.
     */
    public function verifyProvider($id)
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $provider = User::where('role', 'provider')->findOrFail($id);
        
        $provider->is_verified_student = true;
        $provider->save();

        return response()->json([
            'message' => 'Prestataire approuvé avec succès.',
            'provider' => $provider
        ]);
    }

    /**
     * Reject a provider's verification (Optional).
     * Deletes their documents and keeps them unverified.
     */
    public function rejectProvider($id)
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $provider = User::where('role', 'provider')->findOrFail($id);
        
        // Delete documents from storage
        if ($provider->document_id_card) {
            Storage::disk('public')->delete($provider->document_id_card);
        }
        if ($provider->document_student_proof) {
            Storage::disk('public')->delete($provider->document_student_proof);
        }

        $provider->document_id_card = null;
        $provider->document_student_proof = null;
        $provider->save();

        return response()->json([
            'message' => 'Documents rejetés. Le prestataire doit les soumettre à nouveau.'
        ]);
    }

    /**
     * Get all missions (for admin).
     */
    public function allMissions()
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $missions = \App\Models\ServiceRequest::with(['category'])
            ->select(['id', 'title', 'client_name', 'city', 'status', 'budget', 'service_category_id', 'created_at'])
            ->latest()
            ->get();

        return response()->json($missions);
    }

    /**
     * Delete a mission (admin).
     */
    public function deleteMission($id)
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        \App\Models\ServiceRequest::findOrFail($id)->delete();
        return response()->json(['message' => 'Mission supprimée.']);
    }

    /**
     * Delete a provider (admin).
     */
    public function deleteProvider($id)
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        User::where('role', 'provider')->findOrFail($id)->delete();
        return response()->json(['message' => 'Talent supprimé.']);
    }

    /**
     * Platform-wide statistics.
     */
    public function platformStats()
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'total_verified'   => User::where('role', 'provider')->where('is_verified_student', true)->count(),
            'total_pending'    => User::where('role', 'provider')->where('is_verified_student', false)
                                      ->where(fn($q) => $q->whereNotNull('document_id_card')->orWhereNotNull('document_student_proof'))
                                      ->count(),
            'open_missions'    => \App\Models\ServiceRequest::where('status', 'open')->count(),
            'total_offers'     => \App\Models\RequestOffer::count(),
        ]);
    }
}
