<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\PublicRsvpController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/public/events/{website_name}', [EventController::class, 'publicShow']);
Route::get('/public/events/{website_name}/gifts', [GiftController::class, 'publicIndex']);
Route::post('/public/rsvp', [PublicRsvpController::class, 'store'])->middleware('throttle:public-rsvp');

Route::post('/stripe/webhook', StripeWebhookController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::patch('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);

    Route::get('/events/{eventId}/gifts', [GiftController::class, 'index']);
    Route::post('/events/{eventId}/gifts', [GiftController::class, 'store']);
    Route::patch('/gifts/{id}', [GiftController::class, 'update']);
    Route::delete('/gifts/{id}', [GiftController::class, 'destroy']);

    Route::get('/events/{eventId}/guests', [GuestController::class, 'index']);
    Route::post('/events/{eventId}/guests', [GuestController::class, 'store']);
    Route::patch('/guests/{id}', [GuestController::class, 'update']);
    Route::delete('/guests/{id}', [GuestController::class, 'destroy']);
    Route::post('/guests/{id}/invite-token', [GuestController::class, 'regenerateInviteToken']);

    Route::post('/events/{eventId}/stripe/onboarding-link', [StripeController::class, 'createOnboardingLink']);
    Route::post('/events/{eventId}/stripe/dashboard-link', [StripeController::class, 'createDashboardLink']);
    Route::get('/events/{eventId}/stripe/status', [StripeController::class, 'status']);
});
