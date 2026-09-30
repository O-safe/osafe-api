<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class ReassignDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_user_id' => ['required_without:target_family_id', 'nullable', 'string', 'exists:users,user_id'],
            'target_family_id' => ['required_without:target_user_id', 'nullable', 'integer', 'exists:families,family_id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
