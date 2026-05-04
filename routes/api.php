<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\RequestOfferController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceCategoryController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/categories', [ServiceCategoryController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Service Requests
    Route::get('/requests', [ServiceRequestController::class, 'index']);
    Route::post('/requests', [ServiceRequestController::class, 'store']);
    // Note: order is important! /requests/my must come before /requests/{serviceRequest}
    Route::get('/requests/my', [ServiceRequestController::class, 'myRequests']);
    Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show']);
    Route::post('/requests/{serviceRequest}/complete', [ServiceRequestController::class, 'complete']);

    // Offers
    Route::post('/requests/{serviceRequest}/offers', [RequestOfferController::class, 'store']);
    Route::post('/offers/{requestOffer}/accept', [RequestOfferController::class, 'accept']);

    // Reviews
    Route::post('/requests/{serviceRequest}/reviews', [ReviewController::class, 'store']);
});
