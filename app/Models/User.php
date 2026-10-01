<?php

namespace App\Models;

use App\Casts\AccountStatusCast;
use App\Enums\AccountStatus;
use App\Enums\ThemePreference;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Field & attribute configuration
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'contact_number',
        'department_id',
        'position',
        'role',
        'profile_photo_path',
        'is_active',
        'account_status',
        'theme',
        'must_change_password',
        'last_login_at',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'account_status' => AccountStatusCast::class,
            'theme' => ThemePreference::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & helpers
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        return trim(collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->join(' '));
    }

    public function getInitialsAttribute(): string
    {
        $initials = substr($this->first_name, 0, 1).substr($this->last_name, 0, 1);

        return strtoupper($initials);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSupport(): bool
    {
        return $this->role === UserRole::Support;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Requester ? false : true;
    }

    public function isRequester(): bool
    {
        return $this->role === UserRole::Requester;
    }

    /*
    |--------------------------------------------------------------------------
    | Verification status helpers
    |--------------------------------------------------------------------------
    */

    public function isApproved(): bool
    {
        return $this->account_status === AccountStatus::Approved;
    }

    public function isPendingApproval(): bool
    {
        return $this->account_status === AccountStatus::Pending;
    }

    public function isRejected(): bool
    {
        return $this->account_status === AccountStatus::Rejected;
    }

    public function isSuspended(): bool
    {
        return $this->account_status === AccountStatus::Suspended;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class)->latest('submitted_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeStaff($query)
    {
        return $query->whereIn('role', [UserRole::Support->value, UserRole::Admin->value]);
    }
}
