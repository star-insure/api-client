<?php

use Illuminate\Support\Facades\Route;

/**
 * OAuth 2 Single-Sign-On routes
 */
Route::get('/auth/login', fn (\StarInsure\Api\StarAuthService $authService) => $authService->authorize())->name('auth.authorize');
Route::get('/auth/cb', fn (\StarInsure\Api\StarAuthService $authService) => $authService->callback())->name('auth.callback');
Route::get('/auth/sso', fn (\StarInsure\Api\StarAuthService $authService) => $authService->singleSignOn())->name('auth.sso');
Route::post('/auth/revoke', fn (\StarInsure\Api\StarAuthService $authService) => $authService->revokeAll())->name('auth.revoke');
