<?php

namespace Tests\Feature;

use App\Exceptions\WorkflowException;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAssignmentInvariantTest extends TestCase
{
    use RefreshDatabase;

    private TicketWorkflowService $workflow;

    private User $admin;

    private User $support;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->workflow = app(TicketWorkflowService::class);

        $this->admin = User::where('email', 'admin@dccp-bangued.test')->firstOrFail();
        $this->support = User::where('email', 'support@dccp-bangued.test')->firstOrFail();
        $this->requester = User::where('email', 'juan@dccp-bangued.test')->firstOrFail();
    }

    public function test_in_progress_status_requires_an_assignee(): void
    {
        $ticket = $this->unassignedTicket();

        $this->expectException(WorkflowException::class);

        try {
            $this->workflow->changeStatus($ticket, $this->admin, $this->statusByKey('in_progress'));
        } finally {
            $this->assertDatabaseHas('tickets', [
                'id' => $ticket->id,
                'status_id' => $ticket->status_id,
                'assigned_to' => null,
            ]);
        }
    }

    public function test_in_progress_status_is_allowed_once_the_ticket_is_assigned(): void
    {
        $ticket = $this->unassignedTicket();

        $this->workflow->assign($ticket, $this->admin, $this->support);
        $this->workflow->changeStatus($ticket, $this->admin, $this->statusByKey('in_progress'));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status_id' => $this->statusByKey('in_progress')->id,
            'assigned_to' => $this->support->id,
        ]);
    }

    public function test_unassigning_an_in_progress_ticket_returns_it_to_open(): void
    {
        $ticket = $this->unassignedTicket();

        $this->workflow->assign($ticket, $this->admin, $this->support);
        $this->workflow->changeStatus($ticket, $this->admin, $this->statusByKey('in_progress'));
        $this->workflow->unassign($ticket, $this->admin);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status_id' => $this->statusByKey('open')->id,
            'assigned_to' => null,
        ]);
    }

    public function test_requester_reply_leaves_an_unowned_pending_ticket_pending(): void
    {
        $ticket = $this->unassignedTicket();

        $this->workflow->changeStatus($ticket, $this->admin, $this->statusByKey('pending_user'));
        $this->workflow->reply($ticket, $this->requester, 'Here is the information you asked for.');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status_id' => $this->statusByKey('pending_user')->id,
            'assigned_to' => null,
        ]);
    }

    public function test_requester_reply_advances_an_owned_pending_ticket_to_in_progress(): void
    {
        $ticket = $this->unassignedTicket();

        $this->workflow->assign($ticket, $this->admin, $this->support);
        $this->workflow->changeStatus($ticket, $this->admin, $this->statusByKey('pending_user'));
        $this->workflow->reply($ticket, $this->requester, 'Here is the information you asked for.');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status_id' => $this->statusByKey('in_progress')->id,
            'assigned_to' => $this->support->id,
        ]);
    }

    public function test_no_seeded_in_progress_ticket_is_left_without_an_assignee(): void
    {
        $orphans = Ticket::query()
            ->whereNull('assigned_to')
            ->whereHas('status', fn ($query) => $query->where('key', 'in_progress'))
            ->count();

        $this->assertSame(0, $orphans);
    }

    private function unassignedTicket(): Ticket
    {
        return Ticket::factory()->create([
            'requester_id' => $this->requester->id,
            'created_by' => $this->requester->id,
            'assigned_to' => null,
        ]);
    }

    private function statusByKey(string $key): TicketStatus
    {
        return TicketStatus::byKey($key)->firstOrFail();
    }
}
