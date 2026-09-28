<?php

namespace App\Http\Requests\Admin;

use App\Enums\TicketStatusType;
use App\Rules\NotPlaceholderText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $status = $this->route('status');

        return [
            'key' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('ticket_statuses', 'key')->ignore($status?->id)],
            'name' => ['required', 'string', 'max:255', new NotPlaceholderText],
            'color' => ['required', Rule::in(config('tsts.status_colors'))],
            'sort_order' => ['required', 'integer', 'min:1', 'max:'.TicketStatusType::MAX_SORT_ORDER, Rule::unique('ticket_statuses', 'sort_order')->ignore($status?->id)],
            'type' => ['required', Rule::enum(TicketStatusType::class)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sort_order.min' => 'Sort order must be at least 1.',
            'sort_order.max' => 'Sort order must be :max or less. Sort order is a display rank, not a percentage.',
            'sort_order.unique' => 'That sort order is already used by another status. Each status needs its own sort order.',
        ];
    }
}
