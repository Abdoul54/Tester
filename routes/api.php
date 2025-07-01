<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Email verification (signed route) - no auth required
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('api.email.verify');

// Routes requiring authentication only
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/email/resend', [EmailVerificationController::class, 'resend']);
    Route::get('/email/status', [EmailVerificationController::class, 'checkStatus']);
});

// Routes requiring authentication AND email verification
Route::middleware(['auth:sanctum', 'verified.email'])->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
});
