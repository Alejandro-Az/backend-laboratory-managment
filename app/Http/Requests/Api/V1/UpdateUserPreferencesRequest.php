<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notify_urgent_sample_alerts' => ['required', 'boolean'],
            'notify_sample_completion' => ['required', 'boolean'],
            'notify_daily_activity_digest' => ['required', 'boolean'],
            'notify_project_updates' => ['required', 'boolean'],
        ];
    }
}
