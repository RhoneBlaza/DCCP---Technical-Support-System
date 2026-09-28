<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // An existing rejected account may be superseded by a new
        // registration (resubmission). Approved / pending / suspended accounts
        // still block the identifier so identities cannot be reused.
        $rejectedExist = function (string $column) {
            return Rule::unique('users', $column)
                ->where(fn ($q) => $q->whereNotIn('account_status', ['rejected']));
        };

        return [
            'id_type' => ['required', 'string', Rule::in(['school_id', 'employee_id', 'government_id'])],
            'id_number' => ['required', 'string', 'max:100', $rejectedExist('employee_id')],
            'id_image' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $rejectedExist('email')],
            'contact_number' => ['required', 'string', 'max:50'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'position' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers(), 'max:72'],
            'privacy_consent' => ['required', 'accepted'],
            'role' => ['prohibited'],
        ];
    }

    /**
     * Fields that may be written onto the user account.
     */
    public function userAttributes(): array
    {
        return [
            'first_name' => $this->validated('first_name'),
            'middle_name' => $this->validated('middle_name'),
            'last_name' => $this->validated('last_name'),
            'email' => $this->validated('email'),
            'contact_number' => $this->validated('contact_number'),
            'department_id' => $this->validated('department_id'),
            'position' => $this->validated('position'),
            'password' => $this->validated('password'),
        ];
    }
}
