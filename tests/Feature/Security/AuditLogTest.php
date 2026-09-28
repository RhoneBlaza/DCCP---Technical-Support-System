<?php

namespace Tests\Feature\Security;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;

class AuditLogTest extends SecurityTestCase
{
    public function test_attachment_download_is_audited(): void
    {
        $ticket = Ticket::factory()->create([
            'requester_id' => $this->requester->id,
            'created_by' => $this->requester->id,
        ]);

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'message_id' => null,
            'uploaded_by' => $this->support->id,
            'original_name' => 'report.txt',
            'stored_name' => 'report.txt',
            'disk' => 'private',
            'path' => 'attachments/'.$ticket->id.'/report.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
        ]);

        Storage::disk('private')->put($attachment->path, 'hello');

        $this->actingAs($this->support)
            ->get(route('tickets.download', [$ticket, $attachment]))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attachment_downloaded',
            'auditable_id' => $ticket->id,
        ]);
    }

    public function test_audit_log_export_starts_with_a_utf8_bom(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.audit-logs.export'));

        $response->assertOk();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());
    }
}
