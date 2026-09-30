<?php

namespace App\Http\Requests\Geofence;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeofenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'center_latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'center_longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'radius_meters' => ['sometimes', 'integer', 'min:10', 'max:50000'],
            'alert_on_entry' => ['sometimes', 'boolean'],
            'alert_on_exit' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'color' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }
}
