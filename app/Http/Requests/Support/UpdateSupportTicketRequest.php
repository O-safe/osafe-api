<?php

namespace App\Http\Requests\Support;

use App\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'string'],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high', 'critical'])],
            'status' => ['sometimes', 'string', Rule::enum(SupportTicketStatus::class)],
        ];
    }
}
