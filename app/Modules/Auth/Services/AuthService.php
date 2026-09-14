<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function login(LoginRequest $request): bool
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            Log::warning('auth.login_failed', [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
            ]);

            return false;
        }

        $request->session()->regenerate();

        Log::info('auth.login', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
        ]);

        return true;
    }

    public function logout(Request $request): void
    {
        $email = $request->user()?->email;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('auth.logout', ['email' => $email]);
    }
}
