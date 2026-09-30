<?php

namespace App\Http\Requests\DeviceIntegration;

use Illuminate\Foundation\Http\FormRequest;

class IssueDeviceCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['nullable', 'string', 'max:80'],
        ];
    }
}
