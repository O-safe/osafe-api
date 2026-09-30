<?php

namespace App\Http\Requests\DeviceIntegration;

use Illuminate\Foundation\Http\FormRequest;

class DeviceHeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'battery_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'battery_status' => ['nullable', 'string', 'max:50'],
            'ip_address' => ['nullable', 'ip'],
            'network' => ['nullable', 'array'],
            'network.connection_type' => ['nullable', 'string', 'max:50'],
            'network.operator' => ['nullable', 'string', 'max:100'],
            'network.signal_strength' => ['nullable', 'integer', 'min:-140', 'max:100'],
        ];
    }
}
