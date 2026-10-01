<?php

namespace App\Enums;

enum ActivityType: string
{
    case Created = 'created';
    case Assigned = 'assigned';
    case Unassigned = 'unassigned';
    case StatusChanged = 'status_changed';
    case PriorityChanged = 'priority_changed';
    case CategoryChanged = 'category_changed';
    case RequesterReply = 'requester_reply';
    case SupportReply = 'support_reply';
    case InternalNote = 'internal_note';
    case AttachmentUploaded = 'attachment_uploaded';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Reopened = 'reopened';
    case Updated = 'updated';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Ticket created',
            self::Assigned => 'Assigned',
            self::Unassigned => 'Unassigned',
            self::StatusChanged => 'Status changed',
            self::PriorityChanged => 'Priority changed',
            self::CategoryChanged => 'Category changed',
            self::RequesterReply => 'Requester reply',
            self::SupportReply => 'Support reply',
            self::InternalNote => 'Internal note added',
            self::AttachmentUploaded => 'Attachment uploaded',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Reopened => 'Reopened',
            self::Updated => 'Details updated',
        };
    }
}
