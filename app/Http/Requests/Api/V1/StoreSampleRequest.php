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

    public function messages(): array
    {
        return [
            'project_id.required' => 'A project is required.',
            'project_id.integer'  => 'The project identifier must be a number.',
            'project_id.exists'   => 'The selected project does not exist or has been deleted.',
            'code.required'       => 'The sample code is required.',
            'code.max'            => 'The sample code must not exceed 100 characters.',
            'code.unique'         => 'This sample code is already in use.',
            'priority.required'   => 'The priority field is required.',
            'priority.in'         => 'Invalid priority. Valid values are: standard, urgent.',
            'received_at.required' => 'The received date is required.',
            'received_at.date'    => 'The received date must be a valid date.',
        ];
    }
}
