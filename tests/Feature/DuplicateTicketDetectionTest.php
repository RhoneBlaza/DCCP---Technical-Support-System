<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\DuplicateTicketDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DuplicateTicketDetectionTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    private User $otherRequester;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        // Fresh requesters so the ticket counts below only see what each test creates.
        $this->requester = User::factory()->requester()->create();
        $this->otherRequester = User::factory()->requester()->create();
        $this->category = Category::firstOrFail();
    }

    public function test_a_second_identical_open_ticket_is_held_back_until_the_requester_acknowledges_it(): void
    {
        $this->fileOpenTicket('Printer on floor 3 is offline');

        $this->actingAs($this->requester)
            ->from(route('tickets.create'))
            ->post(route('tickets.store'), $this->payload(['subject' => 'Printer on floor 3 is offline']))
            ->assertSessionHasErrors('duplicate_ack');

        $this->assertSame(1, $this->requesterTicketCount());

        $this->actingAs($this->requester)
            ->post(route('tickets.store'), $this->payload([
                'subject' => 'Printer on floor 3 is offline',
                'duplicate_ack' => 1,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->requesterTicketCount());
    }

    public function test_the_warning_names_the_open_ticket_the_requester_should_update_instead(): void
    {
        $existing = $this->fileOpenTicket('Printer on floor 3 is offline');

        $response = $this->actingAs($this->requester)
            ->from(route('tickets.create'))
            ->post(route('tickets.store'), $this->payload(['subject' => 'Printer on floor 3 is offline']));

        $response->assertSessionHasErrors('duplicate_ack');

        $this->assertStringContainsString(
            $existing->ticket_number,
            session('errors')->first('duplicate_ack')
        );
    }

    public function test_the_acknowledgement_box_is_only_offered_after_the_warning(): void
    {
        $this->actingAs($this->requester)->get(route('tickets.create'))->assertOk()->assertDontSee('name="duplicate_ack"', false);

        $this->fileOpenTicket('Printer on floor 3 is offline');

        $this->actingAs($this->requester)
            ->from(route('tickets.create'))
            ->post(route('tickets.store'), $this->payload(['subject' => 'Printer on floor 3 is offline']))
            ->assertRedirect(route('tickets.create'));

        $this->actingAs($this->requester)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('name="duplicate_ack"', false);
    }

    public function test_a_genuinely_different_subject_is_not_treated_as_a_duplicate(): void
    {
        $this->fileOpenTicket('Printer on floor 3 is offline');

        $this->actingAs($this->requester)
            ->post(route('tickets.store'), $this->payload(['subject' => 'Wi-Fi drops in the registrar office']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->requesterTicketCount());
    }

    public function test_another_requester_may_file_the_same_problem(): void
    {
        $this->fileOpenTicket('Printer on floor 3 is offline');

        $this->actingAs($this->otherRequester)
            ->post(route('tickets.store'), $this->payload(['subject' => 'Printer on floor 3 is offline']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $this->requesterTicketCount());
        $this->assertSame(1, Ticket::where('requester_id', $this->otherRequester->id)->count());
    }

    public function test_the_same_subject_in_another_category_is_not_a_duplicate(): void
    {
        $this->fileOpenTicket('Printer on floor 3 is offline');

        $this->actingAs($this->requester)
            ->post(route('tickets.store'), $this->payload([
                'subject' => 'Printer on floor 3 is offline',
                'category_id' => Category::whereKeyNot($this->category->id)->value('id'),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->requesterTicketCount());
    }

    public function test_a_closed_ticket_does_not_block_a_genuinely_new_one(): void
    {
        $existing = $this->fileOpenTicket('Printer on floor 3 is offline');
        $existing->update(['status_id' => TicketStatus::where('key', 'closed')->value('id')]);

        $this->actingAs($this->requester)
            ->post(route('tickets.store'), $this->payload(['subject' => 'Printer on floor 3 is offline']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->requesterTicketCount());
    }

    public function test_an_old_ticket_outside_the_window_does_not_block_a_new_one(): void
    {
        $existing = $this->fileOpenTicket('Printer on floor 3 is offline');
        $existing->forceFill(['created_at' => now()->subDays(3)])->save();

        $this->actingAs($this->requester)
            ->post(route('tickets.store'), $this->payload(['subject' => 'Printer on floor 3 is offline']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->requesterTicketCount());
    }

    public function test_only_whitespace_and_punctuation_differences_still_count_as_the_same_ticket(): void
    {
        $this->fileOpenTicket('Printer on floor 3 is offline');

        $this->actingAs($this->requester)
            ->post(route('tickets.store'), $this->payload(['subject' => '  PRINTER on floor 3 is  --  offline!  ']))
            ->assertSessionHasErrors('duplicate_ack');

        $this->assertSame(1, $this->requesterTicketCount());
    }

    public function test_the_warning_can_be_switched_off(): void
    {
        Config::set('tsts.duplicate_detection.enabled', false);
        $this->fileOpenTicket('Printer on floor 3 is offline');

        $this->actingAs($this->requester)
            ->post(route('tickets.store'), $this->payload(['subject' => 'Printer on floor 3 is offline']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->requesterTicketCount());
    }

    public function test_the_duplicate_keyword_only_counts_the_requested_window(): void
    {
        $this->assertSame(24, (new DuplicateTicketDetector)->windowHours());

        Config::set('tsts.duplicate_detection.window_hours', 4);
        $existing = $this->fileOpenTicket('Printer on floor 3 is offline');
        $existing->forceFill(['created_at' => now()->subHours(6)])->save();

        $this->actingAs($this->requester)
            ->post(route('tickets.store'), $this->payload(['subject' => 'Printer on floor 3 is offline']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->requesterTicketCount());
    }

    public function test_subjects_are_normalized_before_they_are_compared(): void
    {
        $detector = new DuplicateTicketDetector;

        $this->assertSame('printer on floor 3 is offline', $detector->normalize('  Printer  on floor 3 -- is offline! '));
        $this->assertSame('', $detector->normalize('---'));
    }

    private function fileOpenTicket(string $subject): Ticket
    {
        return Ticket::factory()->create([
            'requester_id' => $this->requester->id,
            'category_id' => $this->category->id,
            'subject' => $subject,
            'status_id' => TicketStatus::where('key', 'open')->value('id'),
        ]);
    }

    private function requesterTicketCount(): int
    {
        return Ticket::where('requester_id', $this->requester->id)->count();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'department_id' => $this->requester->department_id,
            'category_id' => $this->category->id,
            'subject' => 'Printer on floor 3 is offline',
            'description' => 'The printer on the third floor stops responding whenever we try to print.',
        ], $overrides);
    }
}
