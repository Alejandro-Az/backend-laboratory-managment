<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('samples', 'code'),
            ],
            'priority' => 'required|in:standard,urgent',
            'received_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
