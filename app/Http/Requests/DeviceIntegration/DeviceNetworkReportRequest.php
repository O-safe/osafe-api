<?php

namespace App\Http\Requests\DeviceIntegration;

use Illuminate\Foundation\Http\FormRequest;

class DeviceNetworkReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'connection_type' => ['required', 'string', 'max:50'],
            'operator' => ['nullable', 'string', 'max:100'],
            'signal_strength' => ['nullable', 'integer', 'min:-140', 'max:100'],
            'ip_address' => ['nullable', 'ip'],
            'mac_address' => ['nullable', 'string', 'max:50'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }
}
