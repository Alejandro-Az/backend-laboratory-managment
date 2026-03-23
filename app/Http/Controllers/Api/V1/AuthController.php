<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\AuthUserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return ApiResponse::error(
                code: 'INVALID_CREDENTIALS',
                message: 'Invalid credentials.',
                status: 401,
            );
        }

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => new AuthUserResource(Auth::guard('api')->user()),
        ], 'Authenticated successfully.');
    }

    public function me(): JsonResponse
    {
        return ApiResponse::success(new AuthUserResource(Auth::guard('api')->user()));
    }

    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return ApiResponse::success((object) [], 'Logged out successfully.');
    }

    public function refresh(): JsonResponse
    {
        $token = Auth::guard('api')->refresh();

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ], 'Token refreshed successfully.');
    }
}
