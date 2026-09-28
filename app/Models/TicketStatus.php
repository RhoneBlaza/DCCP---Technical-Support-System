<?php

namespace App\Models;

use App\Enums\TicketStatusType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    use HasFactory;

    /**
     * System status keys that must always exist and stay active.
     */
    public const ESSENTIAL_KEYS = [
        'open',
        'assigned',
        'in_progress',
        'pending_user',
        'pending_external',
        'resolved',
        'closed',
    ];

    protected $fillable = [
        'key',
        'name',
        'color',
        'sort_order',
        'type',
        'pauses_sla',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'type' => TicketStatusType::class,
            'pauses_sla' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getIsEssentialAttribute(): bool
    {
        return $this->is_system && in_array($this->key, self::ESSENTIAL_KEYS, true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Statuses in display order. The secondary sort keeps the list stable even
     * if a tie ever slips past the unique sort_order index.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }

    /**
     * Prefers the count the index already eager loaded, so rendering the list
     * does not query once per row.
     */
    public function ticketCount(): int
    {
        return (int) ($this->tickets_count ?? Ticket::where('status_id', $this->id)->count());
    }

    /**
     * Why this status may not be deactivated, or null when it may. The list and
     * the toggle share this wording so the button and the refusal agree.
     */
    public function deactivationBlocker(): ?string
    {
        if (! $this->is_active) {
            return null;
        }

        if ($this->is_essential) {
            return 'Essential workflow statuses must stay active so tickets can always be triaged.';
        }

        $count = $this->ticketCount();

        return $count > 0
            ? "This status is used by {$count} ticket(s), so it cannot be deactivated."
            : null;
    }
}
