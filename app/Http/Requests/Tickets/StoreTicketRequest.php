<?php

namespace App\Http\Requests\Tickets;

use App\Models\Priority;
use App\Services\AttachmentService;
use App\Services\DuplicateTicketDetector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'requester_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'location' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'max:255'],
            'asset_number' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'duplicate_ack' => ['nullable', 'boolean'],
        ];

        if ($this->user()->isStaff()) {
            $rules['requester_id'] = ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)];
        }

        return array_merge($rules, (new AttachmentService)->validationRules());
    }

    /**
     * Warn about a near-identical ticket the requester filed moments ago.
     *
     * This is a soft block: the request is only rejected until the requester
     * ticks the confirmation on the create form, so a genuine second problem
     * can still be filed.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->boolean('duplicate_ack')) {
                    return;
                }

                $duplicate = app(DuplicateTicketDetector::class)->findDuplicate(
                    (int) $this->input('requester_id'),
                    (int) $this->input('category_id'),
                    (string) $this->input('subject'),
                );

                if ($duplicate === null) {
                    return;
                }

                $validator->errors()->add(
                    'duplicate_ack',
                    'This looks like a repeat of '.$duplicate->ticket_number.' ("'.$duplicate->subject.'"), which is still open. '
                    .'Add the new detail to that ticket, or confirm below if this is a genuinely different problem.'
                );
            },
        ];
    }

    public function prepareForValidation(): void
    {
        $user = $this->user();

        if (! $user->isStaff()) {
            $this->merge(['requester_id' => $user->id]);
        }

        if (! $this->filled('priority_id')) {
            $defaultPriority = Priority::byKey('normal')->first() ?? Priority::requesterSelectable()->first();

            if ($defaultPriority !== null) {
                $this->merge(['priority_id' => $defaultPriority->id]);
            }
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
        ];
    }
}
