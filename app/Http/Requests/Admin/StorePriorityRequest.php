<?php

namespace App\Http\Requests\Admin;

use App\Rules\NotPlaceholderText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $priority = $this->route('priority');

        return [
            'key' => ['required', 'string', 'max:50', 'alpha_dash', new NotPlaceholderText, Rule::unique('priorities', 'key')->ignore($priority?->id)],
            'name' => ['required', 'string', 'max:255', new NotPlaceholderText],
            'description' => ['nullable', 'string', 'max:2000'],
            'sla_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'level' => ['required', 'integer', 'min:1', 'max:10', Rule::unique('priorities', 'level')->ignore($priority?->id)],
            'is_requester_selectable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'level.min' => 'Level must be at least 1.',
            'level.max' => 'Level must be 10 or less. Level is a relative rank (1 = lowest, 10 = highest), not a percentage.',
            'level.unique' => 'That level is already used by another priority. Each priority needs its own level.',
        ];
    }
}
