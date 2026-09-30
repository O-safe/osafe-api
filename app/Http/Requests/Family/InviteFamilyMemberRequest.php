<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invitee_email' => ['required_without:invitee_phone', 'nullable', 'email', 'max:150'],
            'invitee_phone' => ['required_without:invitee_email', 'nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', Rule::in(['admin', 'member', 'guardian', 'child'])],
        ];
    }
}
