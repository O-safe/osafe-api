<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial_number' => ['required', 'string', 'max:100', 'unique:devices,serial_number'],
            'imei' => ['nullable', 'string', 'max:20', 'unique:devices,imei'],
            'mac_address' => ['nullable', 'string', 'max:17', 'unique:devices,mac_address'],
            'model' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'hardware_version' => ['nullable', 'string', 'max:50'],
            'firmware_version' => ['nullable', 'string', 'max:50'],
            'os_type' => ['nullable', 'string', 'max:50'],
            'os_version' => ['nullable', 'string', 'max:50'],
            'platform' => ['nullable', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:30'],
            'connectivity' => ['nullable', 'string', Rule::in(['wifi', 'cellular', 'bluetooth', 'lte'])],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
