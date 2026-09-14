<?php

use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Organizations\Http\Controllers\OrganizationController;
use App\Modules\YandexIntegration\Http\Controllers\ParsingStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/organization', [OrganizationController::class, 'index']);
    Route::post('/organization/link', [OrganizationController::class, 'saveLink']);
    Route::get('/organization/reviews', [OrganizationController::class, 'reviews']);
    Route::get('/organization/parsing-status', ParsingStatusController::class);
});

Route::get('/ping', fn () => response()->json([
    'pong' => true,
    'time' => now()->toIso8601String(),
]));
