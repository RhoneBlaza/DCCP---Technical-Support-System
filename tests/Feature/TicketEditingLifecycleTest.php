<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the create/edit/reopen paths, the status timestamp trail they leave
 * behind and the admin-only settings that back the branding strings.
 */
class TicketEditingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $support;

    private User $requester;

    private Category $category;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@sample.com')->firstOrFail();
        $this->support = User::where('email', 'support@sample.com')->firstOrFail();
        $this->requester = User::where('email', 'requester@sample.com')->firstOrFail();

        $this->category = Category::whereNull('parent_id')->firstOrFail();
        $this->department = Department::where('is_active', true)->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function test_a_staff_member_can_file_a_ticket_for_a_requester(): void
    {
        $this->actingAs($this->support)
            ->post(route('tickets.store'), $this->payload(['requester_id' => $this->requester->id]))
            ->assertRedirect();

        $ticket = Ticket::where('requester_id', $this->requester->id)->latest('id')->firstOrFail();

        $this->assertSame($this->support->id, $ticket->created_by);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+-\d{4}-\d{6}$/', $ticket->ticket_number);
    }

    public function test_a_staff_member_must_supply_a_valid_requester_id(): void
    {
        $this->actingAs($this->support)
            ->from(route('tickets.create'))
            ->post(route('tickets.store'), $this->payload(['requester_id' => $this->requester->full_name]))
            ->assertSessionHasErrors('requester_id');
    }

    public function test_an_inactive_requester_cannot_be_selected(): void
    {
        $inactive = User::factory()->requester()->inactive()->create();

        $this->actingAs($this->support)
            ->from(route('tickets.create'))
            ->post(route('tickets.store'), $this->payload(['requester_id' => $inactive->id]))
            ->assertSessionHasErrors('requester_id');
    }

    public function test_the_preview_number_endpoint_returns_the_next_number_without_reserving_it(): void
    {
        $before = Ticket::count();

        $response = $this->actingAs($this->requester)
            ->get(route('tickets.preview-number'))
            ->assertOk()
            ->assertJsonStructure(['ticket_number']);

        $this->assertMatchesRegularExpression(
            '/^[A-Z0-9]+-\d{4}-\d{6}$/',
            $response->json('ticket_number')
        );

        $this->assertSame($before, Ticket::count(), 'Previewing a number must not create a ticket.');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function test_the_owner_can_open_the_edit_page(): void
    {
        $ticket = $this->openTicketFor($this->requester);

        $this->actingAs($this->requester)
            ->get(route('tickets.edit', $ticket))
            ->assertOk();
    }

    public function test_the_edit_page_offers_staff_a_requester_selector(): void
    {
        $ticket = $this->openTicketFor($this->requester);

        $this->actingAs($this->support)
            ->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertSee('name="requester_id"', false);
    }

    public function test_a_requester_cannot_open_the_edit_page_for_a_foreign_ticket(): void
    {
        $foreign = $this->openTicketFor(User::factory()->requester()->create());

        $this->actingAs($this->requester)
            ->get(route('tickets.edit', $foreign))
            ->assertNotFound();
    }

    public function test_the_owner_can_update_their_own_open_ticket(): void
    {
        $ticket = $this->openTicketFor($this->requester);

        $this->actingAs($this->requester)
            ->put(route('tickets.update', $ticket), $this->payload(['subject' => 'Updated subject']))
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame('Updated subject', $ticket->fresh()->subject);
        $this->assertTrue(
            $ticket->activities()->where('type', ActivityType::Updated->value)->exists(),
            'An edit should be recorded on the ticket timeline.'
        );
    }

    public function test_a_requester_cannot_edit_another_users_ticket(): void
    {
        $foreign = $this->openTicketFor(User::factory()->requester()->create());

        $this->actingAs($this->requester)
            ->put(route('tickets.update', $foreign), $this->payload())
            ->assertNotFound();
    }

    public function test_a_closed_ticket_cannot_be_edited(): void
    {
        $ticket = $this->openTicketFor($this->requester, 'closed');

        $this->actingAs($this->support)
            ->put(route('tickets.update', $ticket), $this->payload())
            ->assertForbidden();
    }

    public function test_a_requester_cannot_reassign_the_ticket_through_the_payload(): void
    {
        $other = User::factory()->requester()->create();
        $ticket = $this->openTicketFor($this->requester);

        $this->actingAs($this->requester)
            ->put(route('tickets.update', $ticket), $this->payload(['requester_id' => $other->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($this->requester->id, $ticket->fresh()->requester_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Reopen (strict: Resolved only)
    |--------------------------------------------------------------------------
    */

    public function test_the_owner_can_reopen_their_own_resolved_ticket(): void
    {
        $ticket = $this->openTicketFor($this->requester, 'resolved');

        $this->actingAs($this->requester)
            ->from(route('tickets.show', $ticket))
            ->put(route('tickets.reopen', $ticket), ['body' => 'The printer broke again this morning.'])
            ->assertSessionHasNoErrors();

        $ticket->refresh();

        $this->assertSame('reopened', $ticket->status->key);
        $this->assertSame(1, $ticket->reopen_count);
        $this->assertNotNull($ticket->reopened_at);
    }

    public function test_an_open_ticket_cannot_be_reopened(): void
    {
        $ticket = $this->openTicketFor($this->requester, 'open');

        $this->actingAs($this->requester)
            ->from(route('tickets.show', $ticket))
            ->put(route('tickets.reopen', $ticket), ['body' => 'Reopen please.'])
            ->assertForbidden();

        $this->assertSame('open', $ticket->fresh()->status->key);
    }

    public function test_a_requester_cannot_reopen_a_foreign_ticket(): void
    {
        $foreign = $this->openTicketFor(User::factory()->requester()->create(), 'resolved');

        $this->actingAs($this->requester)
            ->put(route('tickets.reopen', $foreign), ['body' => 'Not mine.'])
            ->assertForbidden();
    }

    public function test_reopening_requires_a_reason(): void
    {
        $ticket = $this->openTicketFor($this->requester, 'resolved');

        $this->actingAs($this->requester)
            ->from(route('tickets.show', $ticket))
            ->put(route('tickets.reopen', $ticket), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertSame('resolved', $ticket->fresh()->status->key);
    }

    /*
    |--------------------------------------------------------------------------
    | Status timestamps
    |--------------------------------------------------------------------------
    */

    public function test_creating_a_ticket_stamps_the_status_updated_at(): void
    {
        $ticket = $this->openTicketFor($this->requester);

        $this->assertNotNull($ticket->status_updated_at);
    }

    public function test_resolving_a_ticket_stamps_resolved_at_and_the_status_change(): void
    {
        $ticket = $this->openTicketFor($this->requester, 'open');
        $ticket->forceFill(['status_updated_at' => now()->subDay()])->saveQuietly();

        $this->actingAs($this->support)
            ->put(route('support.resolve', $ticket), ['body' => 'Replaced the toner cartridge.'])
            ->assertSessionHasNoErrors();

        $ticket->refresh();

        $this->assertNotNull($ticket->resolved_at);
        $this->assertTrue($ticket->status_updated_at->gt(now()->subMinute()));
    }

    public function test_reopening_a_ticket_stamps_the_reopened_at(): void
    {
        $ticket = $this->openTicketFor($this->requester, 'resolved');
        $this->assertNull($ticket->reopened_at);

        $this->actingAs($this->requester)
            ->put(route('tickets.reopen', $ticket), ['body' => 'It is failing again.'])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($ticket->fresh()->reopened_at);
    }

    /*
    |--------------------------------------------------------------------------
    | Branding settings
    |--------------------------------------------------------------------------
    */

    public function test_only_an_admin_can_update_the_system_name(): void
    {
        $payload = $this->settingsPayload(['system_name' => 'Bangued Help Desk']);

        $this->actingAs($this->support)
            ->put(route('admin.settings.update'), $payload)
            ->assertForbidden();

        $this->actingAs($this->requester)
            ->put(route('admin.settings.update'), $payload)
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), $payload)
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame('Bangued Help Desk', Setting::where('key', 'system_name')->value('value'));
    }

    public function test_the_changed_system_name_appears_in_the_sidebar_and_title(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), $this->settingsPayload(['system_name' => 'Bangued Help Desk']))
            ->assertRedirect(route('admin.settings.index'));

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bangued Help Desk', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function openTicketFor(User $requester, string $statusKey = 'open'): Ticket
    {
        return Ticket::factory()->create([
            'requester_id' => $requester->id,
            'created_by' => $requester->id,
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'status_id' => TicketStatus::where('key', $statusKey)->value('id'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'subject' => 'Printer on floor 3 is offline',
            'description' => 'The printer on the third floor stops responding whenever we try to print.',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'organization_name' => 'Data Center College of the Philippines - Bangued',
            'system_name' => 'Tech Support Ticketing System',
            'system_short_name' => 'TSTS',
            'support_email' => 'support@sample.com',
            'support_phone' => '09171234567',
            'ticket_prefix' => 'TST',
            'default_priority_id' => Priority::where('key', 'normal')->value('id'),
            'max_attachment_kb' => 5120,
            'allowed_attachment_extensions' => ['pdf', 'png', 'jpg'],
            'max_attachments_per_message' => 5,
            'auto_close_days' => 3,
            'audit_retention_days' => 365,
            'notification_retention_days' => 90,
            'id_image_retention_days' => 90,
        ], $overrides);
    }
}
