<?php

namespace App\Http\Controllers;

use App\Mail\ProviderApproved;
use App\Mail\ProviderRejected;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function pendingCount()
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $count = User::where('role', 'provider')
            ->where('is_verified_student', false)
            ->where(function($q) {
                $q->whereNotNull('document_id_card')
                  ->orWhereNotNull('document_student_proof');
            })
            ->count();

        return ApiResponse::success(['count' => $count], 'Pending count retrieved');
    }

    public function allProviders()
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $providers = User::with('translations')->where('role', 'provider')
            ->select([
                'id', 'first_name', 'last_name', 'email', 'created_at',
                'university', 'field_of_study', 'is_verified_student',
                'document_id_card', 'document_student_proof'
            ])
            ->orderBy('is_verified_student', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success($providers->toArray(), 'Providers retrieved');
    }

    /**
     * Get a list of providers awaiting verification.
     */
    public function pendingProviders()
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
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
            ->with('translations')
            ->orderBy('created_at', 'asc')
            ->get();

        return ApiResponse::success($providers->toArray(), 'Pending providers retrieved');
    }

    /**
     * Show a provider verification document to an authenticated admin.
     */
    public function showProviderDocument(User $provider, string $document)
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
        }

        if ($provider->role !== 'provider') {
            return ApiResponse::error(__('messages.document_not_found'), 404);
        }

        $path = match ($document) {
            'id-card' => $provider->document_id_card,
            'student-proof' => $provider->document_student_proof,
            default => null,
        };

        if (!$path || !Storage::disk('public')->exists($path)) {
            return ApiResponse::error(__('messages.document_not_found'), 404);
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
            return ApiResponse::error('Unauthorized', 403);
        }

        $provider = User::where('role', 'provider')->findOrFail($id);
        
        $provider->is_verified_student = true;
        $provider->save();

        Mail::to($provider->email)->send(new ProviderApproved(
            firstName: $provider->first_name ?? '',
            dashboardUrl: config('services.frontend.url') . '/dashboard'
        ));

        return ApiResponse::success(
            $provider->load('translations')->toArray(),
            __('messages.provider_approved')
        );
    }

    /**
     * Reject a provider's verification (Optional).
     * Deletes their documents and keeps them unverified.
     */
    public function rejectProvider($id)
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
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

        Mail::to($provider->email)->send(new ProviderRejected(
            firstName: $provider->first_name ?? '',
            retryUrl: config('services.frontend.url') . '/talent/register'
        ));

        return ApiResponse::success([], __('messages.documents_rejected'));
    }

    /**
     * Get all missions (for admin).
     */
    public function allMissions()
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $missions = \App\Models\ServiceRequest::with([
            'category.translations',
            'client',
            'selectedProvider.translations',
            'review.translations',
            'translations'
        ])
            ->select([
                'id', 'title', 'client_id', 'client_name', 'city', 'status', 
                'budget', 'service_category_id', 'selected_provider_id', 
                'deadline', 'created_at', 'updated_at'
            ])
            ->latest()
            ->get();

        return ApiResponse::success($missions->toArray(), 'Missions retrieved');
    }

    /**
     * Delete a mission (admin).
     */
    public function deleteMission($id)
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
        }
        \App\Models\ServiceRequest::findOrFail($id)->delete();
        return ApiResponse::success([], __('messages.mission_deleted'));
    }

    /**
     * Delete a provider (admin).
     */
    public function deleteProvider($id)
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
        }
        User::where('role', 'provider')->findOrFail($id)->delete();
        return ApiResponse::success([], __('messages.talent_deleted'));
    }

    /**
     * List all clients (admin).
     */
    public function allClients()
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $clients = User::where('role', 'client')
            ->select([
                'id', 'first_name', 'last_name', 'email', 'city',
                'whatsapp_number', 'created_at',
            ])
            ->with('translations')
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success($clients->toArray(), 'Clients retrieved');
    }

    /**
     * Delete/ban a client (admin).
     */
    public function deleteClient($id)
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
        }
        User::where('role', 'client')->findOrFail($id)->delete();
        return ApiResponse::success([], __('messages.client_deleted'));
    }

    /**
     * Platform-wide statistics.
     */
    public function platformStats()
    {
        if (!auth()->user()->is_admin) {
            return ApiResponse::error('Unauthorized', 403);
        }

        return ApiResponse::success([
            'total_verified'   => User::where('role', 'provider')->where('is_verified_student', true)->count(),
            'total_pending'    => User::where('role', 'provider')->where('is_verified_student', false)
                                      ->where(fn($q) => $q->whereNotNull('document_id_card')->orWhereNotNull('document_student_proof'))
                                      ->count(),
            'open_missions'    => \App\Models\ServiceRequest::where('status', 'open')->count(),
            'total_offers'     => \App\Models\RequestOffer::count(),
        ], 'Platform stats retrieved');
    }
}
