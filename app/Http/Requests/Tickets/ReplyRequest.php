<?php

namespace App\Http\Requests\Tickets;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

class ReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            ['body' => ['required', 'string', 'min:1', 'max:10000']],
            (new AttachmentService)->validationRules()
        );
    }

    public function messages(): array
    {
        return [
            'attachments.*.mimes' => 'Attachments must be one of: :values.',
            'attachments.max' => 'You may attach up to :max files per message.',
        ];
    }
}
