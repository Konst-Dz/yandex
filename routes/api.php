<?php

use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Organizations\Http\Controllers\OrganizationController;
use App\Modules\YandexIntegration\Http\Controllers\ParsingStatusController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/organizations', [OrganizationController::class, 'index']);
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
    Route::patch('/organizations/{organization}', [OrganizationController::class, 'update']);
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);
    Route::get('/organizations/{organization}/reviews', [OrganizationController::class, 'reviews']);
    Route::get('/organizations/{organization}/parsing-status', ParsingStatusController::class);
});

Route::get('/ping', fn () => response()->json([
    'pong' => true,
    'time' => now()->toIso8601String(),
]));
