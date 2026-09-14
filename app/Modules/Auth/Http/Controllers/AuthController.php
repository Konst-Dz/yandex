<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Services\AuthService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!$this->auth->login($request)) {
            return ApiResponse::error('Invalid email or password.', 401);
        }

        return ApiResponse::success($this->userData($request->user()));
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success($this->userData($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request);

        return response()->json()->setStatusCode(204);
    }

    private function userData(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
