<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every list page filters the same way: one search field that applies on its
 * own, with no separate "Filter" button to remember to press.
 */
class ListSearchConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@sample.com')->firstOrFail();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function listPages(): array
    {
        return [
            'tickets' => ['tickets.index', 'q'],
            'users' => ['admin.users.index', 'search'],
            'departments' => ['admin.departments.index', 'search'],
            'categories' => ['admin.categories.index', 'search'],
            'priorities' => ['admin.priorities.index', 'search'],
            'statuses' => ['admin.statuses.index', 'search'],
            'verifications' => ['admin.verifications.index', 'search'],
            'audit logs' => ['admin.audit-logs.index', 'search'],
        ];
    }

    #[DataProvider('listPages')]
    public function test_a_list_page_filters_from_its_search_field_without_a_separate_filter_button(string $routeName, string $parameter): void
    {
        $response = $this->actingAs($this->admin)->get(route($routeName));

        $response->assertOk();
        $response->assertSee('role="search"', false);
        $response->assertSee('type="search"', false);
        $response->assertSee('name="'.$parameter.'"', false);
        $response->assertDontSee('>Filter</button>', false);
    }

    #[DataProvider('listPages')]
    public function test_a_list_page_keeps_the_term_the_requester_typed(string $routeName, string $parameter): void
    {
        $this->actingAs($this->admin)
            ->get(route($routeName, [$parameter => 'zzzz-no-such-value']))
            ->assertOk()
            ->assertSee('value="zzzz-no-such-value"', false);
    }

    public function test_searching_the_ticket_list_narrows_the_results(): void
    {
        Ticket::factory()->create(['subject' => 'Barangay hall projector is broken']);
        Ticket::factory()->create(['subject' => 'Payroll portal rejects the second batch']);

        $this->actingAs($this->admin)
            ->get(route('tickets.index', ['q' => 'projector']))
            ->assertOk()
            ->assertSee('Barangay hall projector is broken')
            ->assertDontSee('Payroll portal rejects the second batch');
    }

    public function test_searching_the_department_list_narrows_the_results(): void
    {
        $needle = Department::create([
            'name' => 'Zambales Mountain District Office',
            'code' => 'ZMDO',
            'is_active' => true,
        ]);

        $other = Department::query()->whereKeyNot($needle->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.departments.index', ['search' => 'Zambales']))
            ->assertOk()
            ->assertSee($needle->name)
            ->assertDontSee($other->name);
    }
}
