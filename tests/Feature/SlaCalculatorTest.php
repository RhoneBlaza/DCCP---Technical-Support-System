<?php

namespace Tests\Feature;

use App\Enums\TicketStatusType;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\SlaCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private SlaCalculator $sla;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->sla = app(SlaCalculator::class);
        $this->requester = User::where('email', 'requester@sample.com')->firstOrFail();
    }

    public function test_a_resolved_ticket_never_reports_overdue(): void
    {
        $ticket = $this->finishedTicket(TicketStatusType::Resolved, resolvedAt: now()->subHours(2));

        $this->assertFalse($this->sla->isOverdue($ticket));
        $this->assertStringNotContainsString('OVERDUE', $this->sla->displayLabel($ticket));
        $this->assertSame('SLA met', $this->sla->displayLabel($ticket));
    }

    public function test_a_closed_ticket_never_reports_overdue(): void
    {
        $ticket = $this->finishedTicket(TicketStatusType::Closed, resolvedAt: now()->subHours(2), closedAt: now()->subHour());

        $this->assertFalse($this->sla->isOverdue($ticket));
        $this->assertStringNotContainsString('OVERDUE', $this->sla->displayLabel($ticket));
        $this->assertSame('SLA met', $this->sla->displayLabel($ticket));
    }

    public function test_a_ticket_finished_after_its_deadline_reports_a_breached_sla(): void
    {
        $ticket = $this->finishedTicket(
            TicketStatusType::Resolved,
            dueAt: now()->subHours(6),
            resolvedAt: now()->subHours(2),
        );

        $this->assertFalse($this->sla->isOverdue($ticket));
        $this->assertSame('breached', $this->sla->finishedOutcome($ticket));
        $this->assertSame('SLA breached', $this->sla->displayLabel($ticket));
    }

    public function test_the_finished_outcome_does_not_drift_as_time_passes(): void
    {
        $ticket = $this->finishedTicket(
            TicketStatusType::Resolved,
            dueAt: now()->subHours(6),
            resolvedAt: now()->subHours(2),
        );

        $outcome = $this->sla->finishedOutcome($ticket);

        $this->travel(30)->days();

        $this->assertSame($outcome, $this->sla->finishedOutcome($ticket));
        $this->assertSame('SLA breached', $this->sla->displayLabel($ticket->fresh()));
    }

    public function test_a_running_ticket_has_no_finished_outcome(): void
    {
        $ticket = $this->finishedTicket(TicketStatusType::Open, dueAt: now()->subHours(6));

        $this->assertNull($this->sla->finishedOutcome($ticket));
        $this->assertTrue($this->sla->isOverdue($ticket));
        $this->assertStringStartsWith('OVERDUE:', $this->sla->displayLabel($ticket));
    }

    public function test_a_running_ticket_reports_its_remaining_time(): void
    {
        $ticket = $this->finishedTicket(TicketStatusType::Open, dueAt: now()->addHours(3));

        $this->assertFalse($this->sla->isOverdue($ticket));
        $this->assertNull($this->sla->finishedOutcome($ticket));
        $this->assertStringEndsWith('remaining', $this->sla->displayLabel($ticket));
        $this->assertGreaterThan(0, $this->sla->minutesRemaining($ticket));
    }

    public function test_minutes_remaining_is_zero_once_a_ticket_is_finished(): void
    {
        $ticket = $this->finishedTicket(
            TicketStatusType::Resolved,
            dueAt: now()->subHours(6),
            resolvedAt: now()->subHours(2),
        );

        $this->assertSame(0, $this->sla->minutesRemaining($ticket));
    }

    public function test_a_paused_ticket_reports_no_overdue(): void
    {
        $status = TicketStatus::factory()->ofType(TicketStatusType::Pending)->create(['pauses_sla' => true]);

        $ticket = Ticket::factory()->withStatus($status)->create([
            'requester_id' => $this->requester->id,
            'created_by' => $this->requester->id,
            'due_at' => now()->subHours(6),
            'sla_paused_at' => now()->subDay(),
        ]);

        $this->assertTrue($this->sla->isPaused($ticket));
        $this->assertFalse($this->sla->isOverdue($ticket));
        $this->assertSame('SLA paused', $this->sla->displayLabel($ticket));
    }

    public function test_the_tickets_page_never_shows_overdue_on_a_finished_ticket(): void
    {
        $this->finishedTicket(
            TicketStatusType::Resolved,
            dueAt: now()->subHours(6),
            resolvedAt: now()->subHours(2),
        );
        $this->finishedTicket(
            TicketStatusType::Closed,
            dueAt: now()->subHours(6),
            resolvedAt: now()->subHours(3),
            closedAt: now()->subHours(2),
        );

        $admin = User::where('email', 'admin@sample.com')->firstOrFail();

        $html = $this->actingAs($admin)->get(route('tickets.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('OVERDUE', $html);
        $this->assertStringContainsString('SLA breached', $html);
    }

    private function finishedTicket(
        TicketStatusType $type,
        ?\DateTimeInterface $dueAt = null,
        ?\DateTimeInterface $resolvedAt = null,
        ?\DateTimeInterface $closedAt = null,
    ): Ticket {
        $status = TicketStatus::factory()->ofType($type)->create();

        return Ticket::factory()->withStatus($status)->create([
            'requester_id' => $this->requester->id,
            'created_by' => $this->requester->id,
            'due_at' => $dueAt ?? now()->addDay(),
            'resolved_at' => $resolvedAt,
            'closed_at' => $closedAt,
        ]);
    }
}
