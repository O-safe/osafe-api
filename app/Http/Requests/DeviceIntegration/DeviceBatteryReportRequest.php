<?php

namespace App\Http\Requests\DeviceIntegration;

use Illuminate\Foundation\Http\FormRequest;

class DeviceBatteryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'battery_level' => ['required', 'numeric', 'min:0', 'max:100'],
            'battery_status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
