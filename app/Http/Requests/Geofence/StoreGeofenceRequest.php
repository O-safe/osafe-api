<?php

namespace App\Http\Requests\Geofence;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeofenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'family_id' => ['nullable', 'integer', 'exists:families,family_id'],
            'center_latitude' => ['required', 'numeric', 'between:-90,90'],
            'center_longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:50000'],
            'shape' => ['nullable', 'string', Rule::in(['circle', 'polygon'])],
            'alert_on_entry' => ['nullable', 'boolean'],
            'alert_on_exit' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:30'],
            'device_ids' => ['nullable', 'array'],
            'device_ids.*' => ['integer', 'exists:devices,device_id'],
        ];
    }
}
