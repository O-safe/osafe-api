<?php

namespace App\Http\Requests\Geofence;

use Illuminate\Foundation\Http\FormRequest;

class DetachDeviceGeofenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'integer', 'exists:devices,device_id'],
        ];
    }
}
