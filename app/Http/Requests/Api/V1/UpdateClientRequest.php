<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Client $client */
        $client = $this->route('client');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('clients', 'name')->ignore($client->id)],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
