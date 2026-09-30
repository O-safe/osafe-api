<?php

namespace App\Http\Requests\Device;

use App\Enums\DeviceCommandType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'command_type' => ['required', 'string', Rule::enum(DeviceCommandType::class)],
            'payload' => ['nullable', 'array'],
            'payload.duration_seconds' => ['sometimes', 'integer', 'min:1', 'max:300'],
            'payload.camera' => ['sometimes', 'string', Rule::in(['front', 'back', 'dual'])],
            'payload.quality' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high'])],
            'payload.settings' => ['required_if:command_type,update_settings', 'array'],
        ];
    }
}
