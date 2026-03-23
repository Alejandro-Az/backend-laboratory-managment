<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSamplePriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority' => 'required|in:standard,urgent',
        ];
    }

    public function messages(): array
    {
        return [
            'priority.required' => 'The priority field is required.',
            'priority.in'       => 'Invalid priority. Valid values are: standard, urgent.',
        ];
    }
}
