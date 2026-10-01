<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewTicketRequesterNotification;
use App\Notifications\NewTicketSupportNotification;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class TicketNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    private User $support;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->requester = User::where('email', 'requester@sample.com')->firstOrFail();
        $this->support = User::where('email', 'support@sample.com')->firstOrFail();
    }

    public function test_requester_ticket_creation_notifies_support_after_commit(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->requester)->post(route('tickets.store'), $this->creationPayload());

        $response->assertRedirect();

        $ticket = Ticket::query()->latest('id')->firstOrFail();

        Notification::assertSentTo($this->support, NewTicketSupportNotification::class, function ($notification) use ($ticket) {
            return $notification->ticket->is($ticket) && $notification->afterCommit === true;
        });

        Notification::assertSentTo($this->requester, NewTicketRequesterNotification::class, function ($notification) use ($ticket) {
            return $notification->ticket->is($ticket) && $notification->afterCommit === true;
        });
    }

    public function test_ticket_notifications_do_not_persist_when_transaction_aborts(): void
    {
        $ticketsBefore = Ticket::query()->count();

        try {
            DB::transaction(function () {
                app(TicketWorkflowService::class)->create($this->requester, $this->servicePayload());

                $this->assertDatabaseHas('notifications', [
                    'type' => NewTicketSupportNotification::class,
                    'notifiable_id' => $this->support->id,
                ]);
                $this->assertDatabaseHas('notifications', [
                    'type' => NewTicketRequesterNotification::class,
                    'notifiable_id' => $this->requester->id,
                ]);

                throw new RuntimeException('Abort the outer transaction.');
            });
        } catch (RuntimeException) {
            // The abort is the point of the test.
        }

        $this->assertSame($ticketsBefore, Ticket::query()->count());
        $this->assertDatabaseMissing('notifications', ['type' => NewTicketSupportNotification::class]);
        $this->assertDatabaseMissing('notifications', ['type' => NewTicketRequesterNotification::class]);
    }

    protected function creationPayload(): array
    {
        return [
            'department_id' => Department::where('code', 'FIN')->value('id'),
            'category_id' => Category::where('name', 'Software')->value('id'),
            'priority_id' => Priority::byKey('normal')->value('id'),
            'subject' => 'Cannot connect to the office Wi-Fi',
            'description' => 'Since this morning my laptop cannot connect to the office Wi-Fi network.',
        ];
    }

    protected function servicePayload(): array
    {
        return array_merge($this->creationPayload(), [
            'requester_id' => $this->requester->id,
        ]);
    }
}
