<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Minimum 10 characters, mixed case, at least one number, and at most
     * 72 bytes so the value is never silently truncated by bcrypt.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if (mb_strlen($value) < 10) {
            $fail('The :attribute must be at least 10 characters.');
        }

        if (strlen($value) > 72) {
            $fail('The :attribute must not be longer than 72 characters.');
        }

        if (preg_match('/[a-z]/', $value) !== 1 || preg_match('/[A-Z]/', $value) !== 1) {
            $fail('The :attribute must contain both uppercase and lowercase letters.');
        }

        if (preg_match('/[0-9]/', $value) !== 1) {
            $fail('The :attribute must contain at least one number.');
        }
    }
}
