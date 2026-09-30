<?php

namespace App\Http\Requests\DeviceIntegration;

use Illuminate\Foundation\Http\FormRequest;

class DeviceCommandAckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:delivered,executed,success,completed,failed,error'],
            'response_payload' => ['nullable', 'array'],
            'error_message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
