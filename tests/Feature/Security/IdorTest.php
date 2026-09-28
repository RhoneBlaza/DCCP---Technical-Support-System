<?php

namespace Tests\Feature\Security;

use App\Enums\MessageType;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;

class IdorTest extends SecurityTestCase
{
    public function test_internal_note_attachment_stays_hidden_after_message_is_soft_deleted(): void
    {
        $ticket = Ticket::factory()->create([
            'requester_id' => $this->requester->id,
            'created_by' => $this->requester->id,
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->support->id,
            'type' => MessageType::Internal->value,
            'body' => 'Internal draft',
        ]);

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'message_id' => $message->id,
            'uploaded_by' => $this->support->id,
            'original_name' => 'notes.txt',
            'stored_name' => 'notes.txt',
            'disk' => 'private',
            'path' => 'attachments/'.$ticket->id.'/notes.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
        ]);

        $message->delete();

        $this->assertTrue($attachment->fresh()->is_internal);

        $this->actingAs($this->requester)
            ->get(route('tickets.download', [$ticket, $attachment]))
            ->assertForbidden();
    }
}
