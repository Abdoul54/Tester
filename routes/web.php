<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// In web.php
Route::get('/email/verify/{id}/{hash}', function (Request $request) {
    $apiUrl = config('app.api_url') . '/api/email/verify/' . $request->route('id') . '/' . $request->route('hash');
    $frontendUrl = config('app.frontend_url') . '/email-verified';

    // You could make a request to your API here or just redirect
    return JsonResponse::create([
        'message' => 'Email verification successful!',
        'redirect_url' => $frontendUrl,
    ])->setStatusCode(200);
})->middleware('signed')->name('verification.verify');
