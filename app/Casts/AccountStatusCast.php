<?php

namespace App\Casts;

use App\Enums\AccountStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast account_status onto the AccountStatus enum, treating a null (legacy)
 * value as "pending" so no call site ever touches a null status.
 */
class AccountStatusCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): AccountStatus
    {
        return AccountStatus::tryFrom($value) ?? AccountStatus::Pending;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof AccountStatus) {
            $value = $value->value;
        }

        return $value ?? AccountStatus::Pending->value;
    }
}
