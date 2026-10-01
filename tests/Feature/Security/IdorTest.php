<?php

namespace Tests\Feature\Security;

use App\Enums\MessageType;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\TicketStatus;
use App\Models\User;

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

    public function test_a_requester_cannot_edit_another_users_ticket(): void
    {
        $foreign = Ticket::factory()->create([
            'requester_id' => User::factory()->requester()->create()->id,
        ]);

        $this->actingAs($this->requester)
            ->get(route('tickets.edit', $foreign))
            ->assertNotFound();

        $this->actingAs($this->requester)
            ->put(route('tickets.update', $foreign), [
                'department_id' => $foreign->department_id,
                'category_id' => $foreign->category_id,
                'subject' => 'Hijacked',
                'description' => 'Trying to overwrite somebody else\'s ticket.',
            ])
            ->assertNotFound();
    }

    public function test_a_requester_cannot_reopen_another_users_ticket(): void
    {
        $foreign = Ticket::factory()->create([
            'requester_id' => User::factory()->requester()->create()->id,
            'status_id' => TicketStatus::where('key', 'resolved')->value('id'),
        ]);

        $this->actingAs($this->requester)
            ->put(route('tickets.reopen', $foreign), ['body' => 'Not mine to reopen.'])
            ->assertForbidden();

        $this->assertSame('resolved', $foreign->fresh()->status->key);
    }
}
