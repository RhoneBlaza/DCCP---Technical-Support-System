<?php

namespace App\Http\Requests\Tickets;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by TicketPolicy::update() in the controller.
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            // Only staff may reassign a ticket to a different requester.
            'requester_id' => $user->isStaff()
                ? ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)]
                : ['required', 'integer', Rule::in([$user->id])],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'location' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'max:255'],
            'asset_number' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
        ];

        // Priority is a staff tool: it has its own action, policy and SLA side
        // effects, so it is only editable here for staff.
        if ($user->isStaff()) {
            $rules['priority_id'] = ['nullable', 'integer', Rule::exists('priorities', 'id')];
        }

        return array_merge($rules, (new AttachmentService)->validationRules());
    }

    /**
     * A requester can never edit the `requester_id` field, so force it to their
     * own id rather than trusting the payload.
     */
    public function prepareForValidation(): void
    {
        if (! $this->user()->isStaff()) {
            $this->merge(['requester_id' => $this->user()->id]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.min' => 'Please provide at least 10 characters of detail so we can triage the request.',
            'attachments.*.mimes' => 'Attachments must be one of: :values.',
            'attachments.max' => 'You may attach up to :max files per message.',
            'requester_id.in' => 'You can only edit your own tickets.',
        ];
    }
}
