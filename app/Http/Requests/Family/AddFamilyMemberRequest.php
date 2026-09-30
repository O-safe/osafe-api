<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'exists:users,user_id'],
            'role' => ['nullable', 'string', Rule::in(['admin', 'member', 'guardian', 'child'])],
            'relationship' => ['nullable', 'string', 'max:50'],
        ];
    }
}
