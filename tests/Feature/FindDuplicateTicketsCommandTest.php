<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The create form stops the next duplicate; this command surfaces the pairs
 * that are already in the table so an agent can close them.
 */
class FindDuplicateTicketsCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->requester = User::factory()->requester()->create();
        $this->category = Category::firstOrFail();
    }

    public function test_it_reports_a_group_of_near_identical_open_tickets(): void
    {
        $first = $this->fileOpenTicket('Barangay hall projector is offline');
        $second = $this->fileOpenTicket('BARANGAY HALL  projector is offline!');

        $this->artisan('tsts:duplicate-tickets')
            ->expectsOutputToContain($first->ticket_number)
            ->expectsOutputToContain($second->ticket_number)
            ->expectsOutputToContain('1 duplicate-looking group(s)')
            ->assertSuccessful();
    }

    public function test_it_stays_quiet_when_there_is_nothing_to_review(): void
    {
        $this->fileOpenTicket('Barangay hall projector is offline');

        $this->artisan('tsts:duplicate-tickets')
            ->expectsOutputToContain('No duplicate-looking tickets found')
            ->assertSuccessful();
    }

    public function test_it_ignores_a_pair_whose_subjects_differ(): void
    {
        $this->fileOpenTicket('Barangay hall projector is offline');
        $this->fileOpenTicket('Barangay hall projector flickers');

        $this->artisan('tsts:duplicate-tickets')
            ->expectsOutputToContain('No duplicate-looking tickets found')
            ->assertSuccessful();
    }

    public function test_it_does_not_list_a_pair_that_already_has_one_closed_ticket(): void
    {
        $this->fileOpenTicket('Barangay hall projector is offline');
        $closing = $this->fileOpenTicket('Barangay hall projector is offline');
        $closing->update(['status_id' => TicketStatus::where('key', 'closed')->value('id')]);

        $this->artisan('tsts:duplicate-tickets')
            ->expectsOutputToContain('No duplicate-looking tickets found')
            ->assertSuccessful();
    }

    public function test_the_window_can_be_widened_from_the_command_line(): void
    {
        $first = $this->fileOpenTicket('Barangay hall projector is offline');
        $first->forceFill(['created_at' => now()->subDays(3)])->save();
        $second = $this->fileOpenTicket('Barangay hall projector is offline');

        $this->artisan('tsts:duplicate-tickets')
            ->expectsOutputToContain('No duplicate-looking tickets found')
            ->assertSuccessful();

        $this->artisan('tsts:duplicate-tickets', ['--window' => 24 * 30])
            ->expectsOutputToContain($first->ticket_number)
            ->expectsOutputToContain($second->ticket_number)
            ->assertSuccessful();
    }

    public function test_it_lists_nothing_while_detection_is_switched_off(): void
    {
        $this->fileOpenTicket('Barangay hall projector is offline');
        $this->fileOpenTicket('Barangay hall projector is offline');

        config(['tsts.duplicate_detection.enabled' => false]);

        $this->artisan('tsts:duplicate-tickets')
            ->expectsOutputToContain('Duplicate detection is disabled')
            ->assertSuccessful();
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
}
