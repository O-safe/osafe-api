<?php

namespace App\Http\Requests\DeviceIntegration;

use App\Enums\DeviceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DeviceStatusReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', new Enum(DeviceStatus::class)],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
