<?php

namespace Tests\Feature;

use App\Models\Priority;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PriorityManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@dccp-bangued.test')->firstOrFail();
    }

    public function test_a_system_priority_can_be_updated_with_its_existing_key(): void
    {
        $priority = Priority::where('key', 'urgent')->firstOrFail();

        $this->actingAs($this->admin)
            ->from(route('admin.priorities.index'))
            ->put(route('admin.priorities.update', $priority), [
                'key' => $priority->key,
                'name' => 'Urgent',
                'description' => 'Critical issues affecting operations; handle immediately.',
                'sla_hours' => 3,
                'level' => $priority->level,
                'is_requester_selectable' => 0,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.priorities.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $priority->fresh()->sla_hours);
    }

    public function test_the_edit_form_never_disables_the_key_field(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.priorities.index'));

        $response->assertOk();

        $this->assertDoesNotMatchRegularExpression(
            '/<input(?=[^>]*\bname="key")(?=[^>]*\bdisabled)/',
            $response->getContent()
        );
    }

    public function test_the_edit_form_locks_system_keys_as_readonly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.priorities.index'));

        $this->assertMatchesRegularExpression(
            '/<input(?=[^>]*\bname="key")(?=[^>]*\breadonly)/',
            $response->getContent()
        );
    }

    public function test_a_duplicate_level_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.priorities.store'), [
                'key' => 'rush',
                'name' => 'Rush',
                'description' => 'Rush work that jumps the queue.',
                'sla_hours' => 2,
                'level' => Priority::where('key', 'urgent')->value('level'),
            ])
            ->assertSessionHasErrors('level');
    }

    public function test_a_priority_may_keep_its_own_level_while_being_edited(): void
    {
        $priority = Priority::factory()->create(['level' => 9]);

        $this->actingAs($this->admin)
            ->put(route('admin.priorities.update', $priority), [
                'key' => $priority->key,
                'name' => 'Renamed',
                'sla_hours' => 12,
                'level' => 9,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $priority->fresh()->name);
    }

    public function test_the_database_rejects_duplicate_levels(): void
    {
        $this->expectException(QueryException::class);

        DB::table('priorities')->insert([
            'key' => 'clash',
            'name' => 'Clash',
            'sla_hours' => 8,
            'level' => Priority::where('key', 'urgent')->value('level'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_a_priority_level_outside_one_to_ten_is_rejected(): void
    {
        foreach ([0, 11, -3, 100] as $level) {
            $this->actingAs($this->admin)
                ->from(route('admin.priorities.index'))
                ->post(route('admin.priorities.store'), [
                    'key' => 'rush-'.abs($level),
                    'name' => 'Rush',
                    'sla_hours' => 2,
                    'level' => $level,
                ])
                ->assertSessionHasErrors('level');
        }

        $this->assertSame(1, Priority::where('key', 'low')->count());
    }

    public function test_the_upper_boundary_of_the_level_range_is_accepted(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.priorities.index'))
            ->post(route('admin.priorities.store'), [
                'key' => 'tier-10',
                'name' => 'Tier 10',
                'sla_hours' => 2,
                'level' => 10,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(10, Priority::where('key', 'tier-10')->value('level'));
    }

    public function test_placeholder_text_cannot_be_saved_as_a_priority(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.priorities.index'))
            ->post(route('admin.priorities.store'), [
                'key' => 'doloremque',
                'name' => 'doloremque',
                'description' => 'Sint a nemo ex minus.',
                'sla_hours' => 2,
                'level' => 7,
            ])
            ->assertSessionHasErrors(['key', 'name']);

        $this->assertNull(Priority::where('key', 'doloremque')->first());
    }

    public function test_ordinary_support_wording_is_still_accepted_as_a_priority(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.priorities.index'))
            ->post(route('admin.priorities.store'), [
                'key' => 'error',
                'name' => 'Error',
                'description' => 'A service is unavailable or an operator cannot sign in.',
                'sla_hours' => 2,
                'level' => 7,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Error', Priority::where('key', 'error')->value('name'));
    }

    public function test_the_create_and_edit_forms_explain_the_description_and_level_fields(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.priorities.index'));

        $response->assertOk();
        $response->assertDontSee('Level (0–100)');
        $response->assertSee('Level (higher = more urgent)', false);
        $response->assertSee('e.g. Standard requests that should be handled within two business days.', false);
    }

    public function test_the_list_reports_how_many_priorities_are_shown(): void
    {
        $total = Priority::count();

        $response = $this->actingAs($this->admin)->get(route('admin.priorities.index'));

        $response->assertOk();
        $this->assertStringContainsString(
            "Showing 1–{$total} of {$total} priorities",
            $this->visibleText($response->getContent())
        );
    }

    /**
     * Reduce a rendered page to its readable text so assertions are not
     * coupled to Blade indentation or tag placement.
     */
    private function visibleText(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)));
    }
}
