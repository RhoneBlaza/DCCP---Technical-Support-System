<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'id_type',
        'id_number',
        'id_image_path',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Human-readable label for the ID type.
     */
    public function getIdTypeLabelAttribute(): string
    {
        return match ($this->id_type) {
            'school_id' => 'School ID',
            'employee_id' => 'Employee ID',
            'government_id' => 'Government ID',
            default => ucfirst((string) $this->id_type),
        };
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'resubmit_requested'], true);
    }
}
