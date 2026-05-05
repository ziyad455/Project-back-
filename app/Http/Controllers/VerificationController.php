<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    /**
     * Upload verification documents for a provider.
     */
    public function uploadDocuments(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'provider') {
            return response()->json(['message' => 'Seuls les prestataires peuvent soumettre des documents.'], 403);
        }

        $request->validate([
            'document_id_card' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120', // 5MB max
            'document_student_proof' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        $uploaded = false;

        if ($request->hasFile('document_id_card')) {
            // Delete old if exists
            if ($user->document_id_card) {
                Storage::disk('public')->delete($user->document_id_card);
            }
            $path = $request->file('document_id_card')->store('verifications', 'public');
            $user->document_id_card = $path;
            $uploaded = true;
        }

        if ($request->hasFile('document_student_proof')) {
            // Delete old if exists
            if ($user->document_student_proof) {
                Storage::disk('public')->delete($user->document_student_proof);
            }
            $path = $request->file('document_student_proof')->store('verifications', 'public');
            $user->document_student_proof = $path;
            $uploaded = true;
        }

        if ($uploaded) {
            $user->save();
            return response()->json([
                'message' => 'Documents envoyés avec succès. En attente de validation.',
                'user' => $user
            ]);
        }

        return response()->json(['message' => 'Aucun document n\'a été fourni.'], 400);
    }
}
