<?php

namespace App\Http\Requests\Admin;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'organization_name' => ['required', 'string', 'max:255'],
            'system_name' => ['required', 'string', 'max:255'],
            'system_short_name' => ['required', 'string', 'max:100'],
            'support_email' => ['required', 'string', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'ticket_prefix' => ['required', 'string', 'max:20', 'alpha_dash'],
            'default_priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'max_attachment_kb' => ['required', 'integer', 'min:100', 'max:51200'],
            'allowed_attachment_extensions' => ['required', 'array', 'min:1'],
            'allowed_attachment_extensions.*' => ['required', 'string', 'max:10'],
            'max_attachments_per_message' => ['required', 'integer', 'min:1', 'max:20'],
            'auto_close_days' => ['required', 'integer', 'min:0', 'max:365'],
            'audit_retention_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'notification_retention_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'id_image_retention_days' => ['required', 'integer', 'min:0', 'max:3650'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $extensions = array_map('strtolower', $this->input('allowed_attachment_extensions', []));

                $allowlist = (new AttachmentService)->allowedExtensions();

                $disallowed = array_values(array_diff($extensions, $allowlist));

                if (! empty($disallowed)) {
                    $validator->errors()->add(
                        'allowed_attachment_extensions',
                        'One or more selected extensions are not permitted: '.implode(', ', $disallowed).'.'
                    );
                }

                $this->merge([
                    'allowed_attachment_extensions' => array_values(array_intersect($extensions, $allowlist)),
                ]);
            },
        ];
    }
}
