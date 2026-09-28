<?php

namespace App\Models;

use App\Enums\TicketStatusType;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'requester_id',
        'created_by',
        'department_id',
        'category_id',
        'priority_id',
        'status_id',
        'assigned_to',
        'subject',
        'description',
        'location',
        'device_type',
        'asset_number',
        'contact_number',
        'due_at',
        'sla_paused_at',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'reopen_count',
        'overdue_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
            'reopen_count' => 'integer',
        ];
    }

    /**
     * Get the route key name for route-model binding ("/tickets/DCCP-2026-000001").
     */
    public function getRouteKeyName(): string
    {
        return 'ticket_number';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at')->orderBy('id');
    }

    public function publicMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)
            ->where('type', 'public')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->orderBy('created_at')->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Restrict ticket queries to what the user is allowed to see.
     * Requesters may only ever see their own tickets; staff see everything.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role !== UserRole::Requester) {
            return $query;
        }

        return $query->where('tickets.requester_id', $user->id);
    }

    public function scopeOpen($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('type', TicketStatusType::Open->value));
    }

    public function scopePending($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('type', TicketStatusType::Pending->value));
    }

    public function scopeResolved($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('type', TicketStatusType::Resolved->value));
    }

    public function scopeClosed($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('type', TicketStatusType::Closed->value));
    }

    public function scopeUnresolved($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->whereNotIn('type', [TicketStatusType::Resolved->value, TicketStatusType::Closed->value]);
        });
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query
            ->where('due_at', '<', now())
            ->whereNull('sla_paused_at')
            ->whereHas('status', function ($q) {
                $q->whereNotIn('type', [TicketStatusType::Resolved->value, TicketStatusType::Closed->value]);
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getIsClosedAttribute(): bool
    {
        return $this->status->type === TicketStatusType::Closed;
    }

    public function getIsResolvedAttribute(): bool
    {
        return $this->status->type === TicketStatusType::Resolved;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status->type === TicketStatusType::Pending;
    }

    public function getDisplaySubjectAttribute(): string
    {
        return Str::limit($this->subject, 60);
    }
}
