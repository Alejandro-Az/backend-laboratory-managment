<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\SampleController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/ping', function () {
        return ApiResponse::success(['service' => 'laboratory-management-api']);
    });

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);

        Route::apiResource('clients', ClientController::class);
        Route::apiResource('projects', ProjectController::class);
        Route::apiResource('samples', SampleController::class);
        Route::post('/samples/{id}/restore', [SampleController::class, 'restore']);
        Route::patch('/samples/{sample}/status', [SampleController::class, 'updateStatus']);
        Route::patch('/samples/{sample}/priority', [SampleController::class, 'updatePriority']);
        Route::post('/samples/{sample}/results', [SampleController::class, 'addResult']);
        Route::get('/samples/{sample}/events', [SampleController::class, 'getEvents']);

        Route::prefix('dashboard')->middleware('can:dashboard.view')->group(function (): void {
            Route::get('/metrics', [DashboardController::class, 'metrics']);
            Route::get('/recent-samples', [DashboardController::class, 'recentSamples']);
            Route::get('/recent-activity', [DashboardController::class, 'recentActivity']);
        });

        Route::prefix('settings')->group(function (): void {
            Route::get('/profile', [SettingsController::class, 'getProfile']);
            Route::patch('/profile', [SettingsController::class, 'updateProfile']);
            Route::get('/preferences', [SettingsController::class, 'getPreferences']);
            Route::patch('/preferences', [SettingsController::class, 'updatePreferences']);
            Route::post('/change-password', [SettingsController::class, 'changePassword']);
        });
    });
});
