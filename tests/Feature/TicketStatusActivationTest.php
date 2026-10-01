<?php

namespace Tests\Feature;

use App\Enums\TicketStatusType;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketStatusActivationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@sample.com')->firstOrFail();
    }

    public function test_an_unused_custom_status_can_be_deactivated_and_reactivated(): void
    {
        $status = TicketStatus::factory()->ofType(TicketStatusType::Open)->create();

        $this->actingAs($this->admin)
            ->from(route('admin.statuses.index'))
            ->patch(route('admin.statuses.toggle-active', $status))
            ->assertRedirect(route('admin.statuses.index'))
            ->assertSessionHas('status', 'Ticket status deactivated.');

        $this->assertFalse($status->fresh()->is_active);

        $this->actingAs($this->admin)
            ->from(route('admin.statuses.index'))
            ->patch(route('admin.statuses.toggle-active', $status))
            ->assertSessionHas('status', 'Ticket status activated.');

        $this->assertTrue($status->fresh()->is_active);
    }

    public function test_an_essential_system_status_cannot_be_deactivated(): void
    {
        $status = TicketStatus::where('key', 'in_progress')->firstOrFail();

        $this->assertTrue($status->is_essential);

        $this->actingAs($this->admin)
            ->from(route('admin.statuses.index'))
            ->patch(route('admin.statuses.toggle-active', $status))
            ->assertSessionHasErrors('error');

        $this->assertTrue($status->fresh()->is_active);
    }

    public function test_a_status_in_use_by_tickets_cannot_be_deactivated(): void
    {
        $status = TicketStatus::factory()->ofType(TicketStatusType::Open)->create(['is_system' => false]);
        Ticket::factory()->withStatus($status)->create();

        $this->actingAs($this->admin)
            ->from(route('admin.statuses.index'))
            ->patch(route('admin.statuses.toggle-active', $status))
            ->assertSessionHasErrors('error');

        $this->assertTrue($status->fresh()->is_active);
    }

    public function test_changing_the_activation_state_is_audited(): void
    {
        $status = TicketStatus::factory()->ofType(TicketStatusType::Open)->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.statuses.toggle-active', $status))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'status_activation_changed',
            'auditable_type' => TicketStatus::class,
            'auditable_id' => $status->id,
        ]);
    }

    public function test_a_non_admin_cannot_change_the_activation_state(): void
    {
        $status = TicketStatus::factory()->ofType(TicketStatusType::Open)->create();
        $support = User::factory()->support()->create();

        $this->actingAs($support)
            ->patch(route('admin.statuses.toggle-active', $status))
            ->assertForbidden();

        $this->assertTrue($status->fresh()->is_active);
    }

    public function test_a_status_needs_a_sort_order_that_no_other_status_uses(): void
    {
        $status = TicketStatus::factory()->ofType(TicketStatusType::Open)->create();

        $this->actingAs($this->admin)
            ->from(route('admin.statuses.index'))
            ->post(route('admin.statuses.store'), [
                'name' => 'Escalated',
                'color' => 'red',
                'type' => TicketStatusType::Open->value,
                'sort_order' => $status->sort_order,
            ])
            ->assertSessionHasErrors('sort_order');
    }

    public function test_a_status_must_be_given_a_sort_order(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.statuses.index'))
            ->post(route('admin.statuses.store'), [
                'name' => 'Escalated',
                'color' => 'red',
                'type' => TicketStatusType::Open->value,
            ])
            ->assertSessionHasErrors('sort_order');
    }

    public function test_a_status_keeps_its_own_sort_order_while_being_edited(): void
    {
        $status = TicketStatus::factory()->ofType(TicketStatusType::Open)->create();

        $this->actingAs($this->admin)
            ->from(route('admin.statuses.index'))
            ->put(route('admin.statuses.update', $status), [
                'key' => $status->key,
                'name' => 'Renamed status',
                'color' => $status->color,
                'type' => $status->type->value,
                'sort_order' => $status->sort_order,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed status', $status->fresh()->name);
    }

    public function test_the_database_rejects_two_statuses_sharing_a_sort_order(): void
    {
        $this->expectException(QueryException::class);

        DB::table('ticket_statuses')->insert([
            'key' => 'clash',
            'name' => 'Clash',
            'color' => 'gray',
            'sort_order' => TicketStatus::where('key', 'open')->value('sort_order'),
            'type' => TicketStatusType::Open->value,
            'pauses_sla' => 0,
            'is_system' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_the_index_disables_the_toggle_where_deactivation_would_be_refused(): void
    {
        $essential = TicketStatus::where('key', 'in_progress')->firstOrFail();
        $inUse = TicketStatus::factory()->ofType(TicketStatusType::Open)->create(['is_system' => false]);
        Ticket::factory()->withStatus($inUse)->create();
        TicketStatus::factory()->ofType(TicketStatusType::Open)->create([
            'is_system' => false,
            'is_active' => false,
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.statuses.index'))
            ->assertOk()
            ->getContent();

        $this->assertHtmlContains($html, 'title="Essential workflow statuses must stay active so tickets can always be triaged."');
        $this->assertHtmlContains($html, 'title="This status is used by 1 ticket(s), so it cannot be deactivated."');
        $this->assertHtmlContains($html, 'title="Deactivate this status"');
        $this->assertHtmlContains($html, 'title="Activate this status"');
        $this->assertHtmlContains($html, route('admin.statuses.toggle-active', $essential));

        $this->assertMatchesRegularExpression(
            '/<button\s+type="submit"\s+disabled[^>]*>\s*Deactivate/',
            $html,
            'The Deactivate button must be disabled where deactivation would be refused.'
        );
    }

    private function assertHtmlContains(string $html, string $needle): void
    {
        $this->assertTrue(
            str_contains($html, $needle),
            'The rendered status list is missing: '.$needle
        );
    }

    public function test_an_inactive_status_can_always_be_activated_even_when_it_has_tickets(): void
    {
        $status = TicketStatus::factory()->ofType(TicketStatusType::Open)->create(['is_active' => false]);
        Ticket::factory()->withStatus($status)->create();

        $this->assertNull($status->deactivationBlocker());

        $this->actingAs($this->admin)
            ->from(route('admin.statuses.index'))
            ->patch(route('admin.statuses.toggle-active', $status))
            ->assertSessionHas('status', 'Ticket status activated.');

        $this->assertTrue($status->fresh()->is_active);
    }

    public function test_the_index_reads_the_ticket_count_without_an_extra_query_per_row(): void
    {
        $status = TicketStatus::factory()->ofType(TicketStatusType::Open)->create(['is_system' => false]);
        Ticket::factory()->withStatus($status)->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.statuses.index'))
            ->assertOk()
            ->assertSee('title="This status is used by 3 ticket(s), so it cannot be deactivated."', false);
    }

    public function test_the_index_renders_statuses_in_ascending_sort_order(): void
    {
        TicketStatus::factory()->ofType(TicketStatusType::Open)->create([
            'name' => 'Zzz Last',
            'sort_order' => 900,
        ]);
        TicketStatus::factory()->ofType(TicketStatusType::Open)->create([
            'name' => 'Aaa First',
            'sort_order' => 200,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.statuses.index'));

        $response->assertOk();
        $response->assertSee('Sort order', false);

        $this->assertStringContainsString(
            'Aaa First',
            $response->getContent()
        );

        $this->assertLessThan(
            strpos($response->getContent(), 'Zzz Last'),
            strpos($response->getContent(), 'Aaa First'),
            'Statuses with a lower sort order should be rendered first.'
        );
    }
}
