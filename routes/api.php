<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\MissionCandidateController;
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
    Route::post('/register/talent', [AuthController::class, 'registerTalent']);
    Route::post('/login', [AuthController::class, 'login']);
    // OTP routes
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1');
    // Password reset routes
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    // Google OAuth
    Route::get('/google/redirect', [\App\Http\Controllers\Api\Auth\GoogleAuthController::class, 'redirect']);
    Route::get('/google/callback', [\App\Http\Controllers\Api\Auth\GoogleAuthController::class, 'callback']);
});

// Public discovery routes (no auth required)
Route::get('/categories', [ServiceCategoryController::class, 'index']);
Route::get('/users', [UserController::class, 'index']);
Route::get('/providers', [UserController::class, 'providers']);
Route::get('/providers/{id}', [UserController::class, 'show']);
Route::post('/missions', [ServiceRequestController::class, 'storePublic']);
Route::get('/providers/{providerId}/reviews', [ReviewController::class, 'index']);
Route::post('/requests', [ServiceRequestController::class, 'store']);
Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show']);
Route::get('/missions/{serviceRequest}/candidates', [MissionCandidateController::class, 'candidates']);
Route::put('/missions/{serviceRequest}/choose/{talent}', [MissionCandidateController::class, 'choose']);

// ── Protected routes ───────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/provider/stats', [ServiceRequestController::class, 'getStats']);
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Own profile
    Route::get('/profile', [UserController::class, 'myProfile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::get('/talent/stats', [UserController::class, 'talentStats']);
    Route::post('/user/avatar', [UserController::class, 'updateAvatar']);
    Route::post('/user/cover-image', [UserController::class, 'updateCoverImage']);
    Route::patch('/user/phone', [UserController::class, 'updatePhone']);
    // Service Requests
    Route::get('/requests', [ServiceRequestController::class, 'index']);
    Route::get('/requests/my', [ServiceRequestController::class, 'myRequests']);
    Route::get('/requests/{serviceRequest}/client-contact', [ServiceRequestController::class, 'clientContact']);
    Route::post('/requests/{serviceRequest}/complete', [ServiceRequestController::class, 'complete']);

    // Offers (talent self-selection)
    Route::get('/offers/my', [RequestOfferController::class, 'myOffers']);
    Route::post('/requests/{serviceRequest}/offers', [RequestOfferController::class, 'store']);
    Route::post('/requests/{serviceRequest}/refuse', [RequestOfferController::class, 'refuse']);
    // Route::post('/offers/{requestOffer}/accept', [RequestOfferController::class, 'accept']); // DEPRECATED — clients no longer accept offers

    // Missions (new talent application flow)
    Route::post('/missions/{serviceRequest}/apply', [MissionCandidateController::class, 'apply']);
    Route::delete('/missions/{serviceRequest}/withdraw', [MissionCandidateController::class, 'withdraw']);

    // Reviews
    Route::post('/requests/{serviceRequest}/reviews', [ReviewController::class, 'store']);
    // DEPRECATED: Direct provider contact removed — use /requests/{id}/client-contact instead
    // Route::get('/providers/{id}/contact', [UserController::class, 'contact']);

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
        Route::get('/providers/{provider}/documents/{document}', [AdminController::class, 'showProviderDocument'])
            ->whereIn('document', ['id-card', 'student-proof']);
        Route::post('/verify-provider/{id}', [AdminController::class, 'verifyProvider']);
        Route::post('/reject-provider/{id}', [AdminController::class, 'rejectProvider']);
        Route::delete('/provider/{id}', [AdminController::class, 'deleteProvider']);
        Route::get('/all-missions', [AdminController::class, 'allMissions']);
        Route::delete('/mission/{id}', [AdminController::class, 'deleteMission']);
        Route::get('/stats', [AdminController::class, 'platformStats']);
        Route::get('/all-clients', [AdminController::class, 'allClients']);
        Route::delete('/client/{id}', [AdminController::class, 'deleteClient']);
    });
});
