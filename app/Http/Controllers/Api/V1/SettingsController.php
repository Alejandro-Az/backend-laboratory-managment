<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Requests\Api\V1\UpdateUserPreferencesRequest;
use App\Http\Resources\SettingsProfileResource;
use App\Http\Resources\UserPreferencesResource;
use App\Models\UserPreference;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function getProfile(Request $request): JsonResponse
    {
        return ApiResponse::success(new SettingsProfileResource($request->user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return ApiResponse::success(new SettingsProfileResource($user->refresh()), 'Profile updated successfully.');
    }

    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'notify_urgent_sample_alerts' => false,
                'notify_sample_completion' => false,
                'notify_daily_activity_digest' => false,
                'notify_project_updates' => false,
            ]
        );

        return ApiResponse::success(new UserPreferencesResource($preferences));
    }

    public function updatePreferences(UpdateUserPreferencesRequest $request): JsonResponse
    {
        $user = $request->user();
        $preferences = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return ApiResponse::success(new UserPreferencesResource($preferences), 'Preferences updated successfully.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error(
                code: 'INVALID_PASSWORD',
                message: 'The current password is incorrect.',
                status: 422,
            );
        }

        $user->update(['password' => $request->password]);

        return ApiResponse::success((object) [], 'Password changed successfully.');
    }
}
