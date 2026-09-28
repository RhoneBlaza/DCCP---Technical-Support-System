<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Model attributes that must never end up in audit logs.
     */
    protected const SENSITIVE_KEYS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function __construct(protected Request $request) {}

    /**
     * Write an audit log entry.
     *
     * @param  Model|null  $model  auditable model
     */
    public function log(
        string $action,
        ?Model $model = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $actorId = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model?->getKey(),
            'description' => $description,
            'old_values' => $oldValues ? $this->sanitize($oldValues) : null,
            'new_values' => $newValues ? $this->sanitize($newValues) : null,
            'ip_address' => $this->request->ip(),
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 500),
        ]);
    }

    /**
     * Remove sensitive keys from stored attribute arrays before persisting.
     */
    protected function sanitize(array $values, string $prefix = ''): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            $fullKey = $prefix !== '' ? $prefix.'.'.$key : $key;

            if (in_array($key, self::SENSITIVE_KEYS, true) || in_array($fullKey, self::SENSITIVE_KEYS, true)) {
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value, $fullKey);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
