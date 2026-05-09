<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{


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
}
