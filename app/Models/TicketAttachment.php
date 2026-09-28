<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'message_id',
        'uploaded_by',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function message(): BelongsTo
    {
        // Include soft-deleted messages so an attachment that belonged to an
        // internal note never silently flips to "public" visibility when the
        // note is removed.
        return $this->belongsTo(TicketMessage::class)->withTrashed();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Whether the attachment lives on an internal note. Uploads that aren't
     * attached to a message default to the "public" visibility.
     */
    public function getIsInternalAttribute(): bool
    {
        return $this->message !== null && $this->message->is_internal;
    }
}
