<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class AssignDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required_without:family_id', 'nullable', 'string', 'exists:users,user_id'],
            'family_id' => ['required_without:user_id', 'nullable', 'integer', 'exists:families,family_id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
