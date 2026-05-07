<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\RequestOfferController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);

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
});
