<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Smoke-маршрут каркаса: проверка связки nginx -> php-fpm -> Laravel
Route::get('/ping', fn () => response()->json([
    'pong' => true,
    'time' => now()->toIso8601String(),
]));

