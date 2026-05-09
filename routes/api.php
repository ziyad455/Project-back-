<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\RequestOfferController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

// ── Public routes ──────────────────────────────────────────────────────────
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// For backward compatibility if needed
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public discovery routes (no auth required)
Route::get('/categories', [ServiceCategoryController::class, 'index']);
Route::get('/providers', [UserController::class, 'providers']);
Route::get('/providers/{id}', [UserController::class, 'show']);

// ── Protected routes ───────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);

    // Own profile
    Route::get('/profile', [UserController::class, 'myProfile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);

    // Service Requests
    Route::get('/requests', [ServiceRequestController::class, 'index']);
    Route::post('/requests', [ServiceRequestController::class, 'store']);
    Route::get('/requests/my', [ServiceRequestController::class, 'myRequests']);
    Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show']);
    Route::post('/requests/{serviceRequest}/complete', [ServiceRequestController::class, 'complete']);

    // Offers
    Route::post('/requests/{serviceRequest}/offers', [RequestOfferController::class, 'store']);
    Route::post('/offers/{requestOffer}/accept', [RequestOfferController::class, 'accept']);

    // Reviews
    Route::post('/requests/{serviceRequest}/reviews', [ReviewController::class, 'store']);
    Route::get('/providers/{providerId}/reviews', [ReviewController::class, 'index']);

    // Notifications
    Route::get('/notifications', fn() => response()->json(auth()->user()->notifications));
    Route::post('/notifications/read-all', fn() => tap(auth()->user()->unreadNotifications->markAsRead()));
    Route::post('/notifications/{id}/read', fn($id) => tap(auth()->user()->notifications()->findOrFail($id)->markAsRead()));

    // Document Verification Route
    Route::post('/verification/upload', [VerificationController::class, 'uploadDocuments']);

    // Admin Routes (Protected by internal isAdmin check in controller)
    Route::prefix('admin')->group(function () {
        Route::get('/pending-count', [AdminController::class, 'pendingCount']);
        Route::get('/all-providers', [AdminController::class, 'allProviders']);
        Route::get('/pending-providers', [AdminController::class, 'pendingProviders']);
        Route::post('/verify-provider/{id}', [AdminController::class, 'verifyProvider']);
        Route::post('/reject-provider/{id}', [AdminController::class, 'rejectProvider']);
    });
});
