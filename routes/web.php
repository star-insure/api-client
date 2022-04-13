<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use StarInsure\Api\Http\Controllers\AuthController;

/**
 * Auth routes
 */
Route::get('/login', [AuthController::class, 'login'])->name('auth.login');
Route::get('/logout', [AuthController::class, 'logout'])->name('auth.logout');

/**
 * Route for handling POST webhooks from the auth server
 */
Route::post('/auth/cb', function () {
    // Get our request data
    $accessToken = request('accessToken');
    $refreshToken = request('refreshToken');
    $sessionId = request('sessionId');
    $user = request('user');

    // Find the session by ID
    $session = DB::table('sessions')->where('id', $sessionId)->first();

    // No session found, don't go further
    if (! $session) {
        return;
    }

    // Unserialize the payload so we can access properties
    $payload = unserialize(base64_decode($session->payload));

    // Update the payloads data to include our tokens
    $payload['access_token'] = $accessToken;
    $payload['refresh_token'] = $refreshToken;
    $payload['user'] = $user;

    // Reserialize and encode the payload
    $payload = base64_encode(serialize($payload));

    // Insert updated session payload in to the database
    DB::table('sessions')->where('id', $sessionId)->update(['payload' => $payload]);
});
