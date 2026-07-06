<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OffenceImageController;
use App\Http\Controllers\Api\OffenceSyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes  (all prefixed with /api)
|--------------------------------------------------------------------------
*/

// Public — login. Throttled to slow down brute-force attempts.
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1');   // max 6 attempts per minute per IP

// Protected — everything here needs a valid Sanctum bearer token.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Sync
    Route::post('/sync/offences', [OffenceSyncController::class, 'push']);
    Route::get('/sync/offences', [OffenceSyncController::class, 'pull']);
    Route::post('/sync/images/{image}/file', [OffenceImageController::class, 'upload']);
});
