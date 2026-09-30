<?php

namespace App\Http\Requests\DeviceIntegration;

use Illuminate\Foundation\Http\FormRequest;

class DeviceLocationIngestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'altitude' => ['nullable', 'numeric'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'source' => ['nullable', 'string', 'max:50'],
            'is_mock' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:255'],
            'recorded_at' => ['nullable', 'date'],
            'device_id' => ['nullable', 'integer'],
        ];
    }
}
