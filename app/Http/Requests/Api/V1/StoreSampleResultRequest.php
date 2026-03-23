<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSampleResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'result_summary' => 'required|string|max:5000',
            'result_data' => 'nullable|array',
        ];
    }
}
