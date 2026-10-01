<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The ticket filter bar applies itself: the dropdowns submit on change, the
 * search debounces, and the date range applies per field. There is no "Apply
 * Filters" button to fall back on, so these cover the query-string contract the
 * controls rely on.
 */
class TicketListFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@sample.com')->firstOrFail();
    }

    public function test_the_filter_bar_has_no_apply_button_and_no_helper_text(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertDontSee('Apply Filters');
        $response->assertDontSee('Dropdowns apply automatically');
        $response->assertSee('role="search"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('name="start_date"', false);
        $response->assertSee('name="end_date"', false);
    }

    public function test_the_status_dropdown_narrows_the_ticket_list(): void
    {
        $open = TicketStatus::where('key', 'open')->firstOrFail();
        $resolved = TicketStatus::where('key', 'resolved')->firstOrFail();

        Ticket::factory()->withStatus($open)->create(['subject' => 'Still open on the floor']);
        Ticket::factory()->withStatus($resolved)->create(['subject' => 'Resolved last week']);

        $this->actingAs($this->admin)
            ->get(route('tickets.index', ['status' => 'resolved']))
            ->assertOk()
            ->assertSee('Resolved last week')
            ->assertDontSee('Still open on the floor');
    }

    public function test_the_date_range_narrows_the_ticket_list(): void
    {
        $this->createDatedTicket('Old ticket from January', '2024-01-15 09:00:00');
        $this->createDatedTicket('June ticket', '2024-06-15 09:00:00');

        $this->actingAs($this->admin)
            ->get(route('tickets.index', ['start_date' => '2024-06-01', 'end_date' => '2024-06-30']))
            ->assertOk()
            ->assertSee('June ticket')
            ->assertDontSee('Old ticket from January');
    }

    public function test_a_reversed_date_range_is_normalized_instead_of_returning_nothing(): void
    {
        $this->createDatedTicket('Old ticket from January', '2024-01-15 09:00:00');
        $this->createDatedTicket('June ticket', '2024-06-15 09:00:00');

        $this->actingAs($this->admin)
            ->get(route('tickets.index', ['start_date' => '2024-06-30', 'end_date' => '2024-06-01']))
            ->assertOk()
            ->assertSee('June ticket')
            ->assertDontSee('Old ticket from January');
    }

    public function test_active_filters_survive_pagination(): void
    {
        $open = TicketStatus::where('key', 'open')->firstOrFail();

        Ticket::factory()->count(26)->withStatus($open)->create();

        $html = $this->actingAs($this->admin)
            ->get(route('tickets.index', ['status' => 'open']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('status=open', $html, 'The filter must be carried into the pagination links.');
        $this->assertStringContainsString('page=2', $html);
    }

    private function createDatedTicket(string $subject, string $createdAt): Ticket
    {
        return Ticket::factory()->create([
            'subject' => $subject,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
