<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $support;

    private User $requester;

    private Ticket $ticket;

    private Ticket $foreignTicket;

    private Ticket $resolvedTicket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@dccp-bangued.test')->firstOrFail();
        $this->support = User::where('email', 'support@dccp-bangued.test')->firstOrFail();
        $this->requester = User::where('email', 'juan@dccp-bangued.test')->firstOrFail();

        $this->ticket = Ticket::where('requester_id', $this->requester->id)->firstOrFail();

        $otherRequester = User::factory()->requester()->create();
        $this->foreignTicket = Ticket::factory()->create([
            'requester_id' => $otherRequester->id,
            'created_by' => $otherRequester->id,
        ]);

        $this->resolvedTicket = Ticket::factory()->create([
            'requester_id' => $this->requester->id,
            'created_by' => $this->requester->id,
            'status_id' => TicketStatus::where('key', 'resolved')->value('id'),
        ]);
    }

    public function test_every_guarded_model_resolves_to_a_policy(): void
    {
        foreach ([
            User::class,
            Setting::class,
            AuditLog::class,
            Department::class,
            Category::class,
            Priority::class,
            TicketStatus::class,
            Ticket::class,
            VerificationRequest::class,
        ] as $class) {
            $this->assertNotNull(Gate::getPolicyFor($class), "No policy discovered for {$class}.");
        }
    }

    public function test_admin_only_policies_are_enforced_by_role(): void
    {
        foreach ([
            User::class,
            Setting::class,
            AuditLog::class,
            VerificationRequest::class,
        ] as $class) {
            $this->assertTrue($this->admin->can('viewAny', $class), "admin cannot viewAny on {$class}");
            $this->assertFalse($this->support->can('viewAny', $class), "support CAN viewAny on {$class}");
            $this->assertFalse($this->requester->can('viewAny', $class), "requester CAN viewAny on {$class}");
        }
    }

    public function test_admin_can_load_the_verification_and_activity_pages(): void
    {
        $this->actingAs($this->admin)->get(route('admin.verifications.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_staff_scoped_policies_allow_support_but_not_requesters(): void
    {
        foreach ([
            Department::class,
            Category::class,
            Priority::class,
            TicketStatus::class,
        ] as $class) {
            $this->assertTrue($this->admin->can('viewAny', $class), "admin cannot viewAny on {$class}");
            $this->assertTrue($this->support->can('viewAny', $class), "support cannot viewAny on {$class}");
            $this->assertFalse($this->requester->can('viewAny', $class), "requester CAN viewAny on {$class}");
        }
    }

    public function test_ticket_policy_abilities_are_role_aware(): void
    {
        $this->assertTrue($this->admin->can('viewAny', Ticket::class));
        $this->assertTrue($this->support->can('viewAny', Ticket::class));
        $this->assertTrue($this->requester->can('viewAny', Ticket::class));

        $this->assertTrue($this->admin->can('create', Ticket::class));
        $this->assertTrue($this->support->can('create', Ticket::class));
        $this->assertTrue($this->requester->can('create', Ticket::class));

        $this->assertTrue($this->admin->can('view', $this->ticket));
        $this->assertTrue($this->support->can('view', $this->ticket));
        $this->assertTrue($this->requester->can('view', $this->ticket));

        $this->assertTrue($this->support->can('assign', $this->ticket));
        $this->assertTrue($this->support->can('manage', $this->ticket));
        $this->assertFalse($this->requester->can('manage', $this->foreignTicket));

        $this->assertTrue($this->support->can('reopen', $this->resolvedTicket));
        $this->assertFalse($this->requester->can('reopen', $this->ticket));
        $this->assertTrue($this->requester->can('reopen', $this->resolvedTicket));
        $this->assertFalse($this->requester->can('reopen', $this->foreignTicket));
    }

    public function test_support_can_load_the_queue_and_staff_pages(): void
    {
        $this->actingAs($this->support)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->support)->get(route('support.queue'))->assertOk();
        $this->actingAs($this->support)->get(route('support.my-tickets'))->assertOk();
        $this->actingAs($this->support)->get(route('support.all-tickets'))->assertOk();
        $this->actingAs($this->support)->get(route('tickets.create'))->assertOk();
        $this->actingAs($this->support)->get(route('reports.export'))->assertOk();
    }

    public function test_admin_can_load_staff_and_admin_pages(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('tickets.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('support.queue'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.users.edit', $this->admin))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.departments.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.categories.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.priorities.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.statuses.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.settings.index'))->assertOk();
    }

    public function test_admin_can_load_configuration_and_report_pages(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('notifications.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('search.index', ['q' => $this->ticket->ticket_number]))->assertOk();
    }

    public function test_requester_can_load_requester_pages_and_is_blocked_from_staff_pages(): void
    {
        $this->actingAs($this->requester)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->requester)->get(route('tickets.index'))->assertOk();
        $this->actingAs($this->requester)->get(route('tickets.my-tickets'))->assertOk();
        $this->actingAs($this->requester)->get(route('tickets.create'))->assertOk();

        $this->actingAs($this->requester)->get(route('support.queue'))->assertForbidden();
        $this->actingAs($this->requester)->get(route('support.my-tickets'))->assertForbidden();
        $this->actingAs($this->requester)->get(route('support.all-tickets'))->assertForbidden();
        $this->actingAs($this->requester)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($this->requester)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_support_is_blocked_from_admin_pages(): void
    {
        $this->actingAs($this->support)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_requester_gets_404_on_another_users_ticket(): void
    {
        $this->actingAs($this->requester)
            ->get(route('tickets.show', $this->foreignTicket))
            ->assertNotFound();
    }

    public function test_staff_can_load_the_ticket_show_page(): void
    {
        $response = $this->actingAs($this->support)->get(route('tickets.show', $this->ticket));

        $response->assertOk();
        $response->assertSee($this->ticket->ticket_number);
        $response->assertSee('Internal note');
    }

    public function test_support_can_load_the_reports_page(): void
    {
        $response = $this->actingAs($this->support)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Tickets created');
        $response->assertSee('Resolution rate');
    }

    public function test_support_dashboard_renders_its_workload_tables(): void
    {
        $html = $this->actingAs($this->support)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Unassigned tickets', $html);
        $this->assertStringContainsString('My assigned tickets', $html);
        $this->assertStringContainsString('Overdue tickets', $html);
    }

    public function test_admin_dashboard_renders_charts_with_matching_titles(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Tickets by status', $html);
        $this->assertStringContainsString('Tickets by category', $html);
        $this->assertStringContainsString('trendChart', $html);
        $this->assertStringContainsString('7 days', $html);
        $this->assertStringContainsString('30 days', $html);
    }

    public function test_admin_dashboard_open_tile_matches_the_status_chart_totals(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Open', $html);
        $this->assertStringNotContainsString('Open / unassigned', $html);

        $openStatusTotal = Ticket::whereHas('status', fn ($q) => $q->where('key', 'open'))->count();

        $this->assertMatchesRegularExpression(
            '/>Open<\/p>\s*<p class="text-2xl[^"]*">\s*'.$openStatusTotal.'\s*<\/p>/',
            $html,
            'The Open tile counts the tickets in the open status, which is the same set the donut slice shows.'
        );
    }

    public function test_admin_dashboard_status_legend_lists_every_active_status(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Open (', $html);
        $this->assertStringNotContainsString('quis illum', $html);

        foreach (TicketStatus::active()->ordered()->get() as $status) {
            $this->assertStringContainsString(
                $status->name.' (',
                $html,
                'Every real status belongs in the legend, including the ones with no tickets yet.'
            );
        }
    }

    public function test_requester_ticket_pages_do_not_expose_internal_fields(): void
    {
        $html = $this->actingAs($this->requester)
            ->get(route('tickets.my-tickets'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('My tickets', $html);
        $this->assertStringNotContainsString('Any priority', $html);
        $this->assertStringNotContainsString('Any category', $html);
        $this->assertStringNotContainsString('Any department', $html);
        $this->assertStringNotContainsString('Due / SLA', $html);
    }
}
