<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Priority extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'sla_hours',
        'level',
        'is_requester_selectable',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'sla_hours' => 'integer',
            'level' => 'integer',
            'is_requester_selectable' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequesterSelectable($query)
    {
        return $query->active()->where('is_requester_selectable', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('level', 'desc');
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }
}
