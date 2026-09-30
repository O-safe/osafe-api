<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'color' => ['sometimes', 'nullable', 'string', 'max:30'],
            'firmware_version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'os_version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'connectivity' => ['sometimes', 'string', Rule::in(['wifi', 'cellular', 'bluetooth', 'lte'])],
            'push_token' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
