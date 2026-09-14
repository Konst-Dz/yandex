<?php

use Illuminate\Support\Facades\Route;

// SPA catch-all: все не-API маршруты отдают один blade-шаблон,
// маршрутизация внутри приложения — на клиенте (vue-router, history mode).
// api-маршруты (routes/api.php) регистрируются раньше web и не перехватываются.
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
