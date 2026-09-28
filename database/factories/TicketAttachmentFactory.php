<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketAttachmentFactory extends Factory
{
    protected $model = TicketAttachment::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'message_id' => null,
            'uploaded_by' => User::factory(),
            'original_name' => $this->faker->word().'.pdf',
            'stored_name' => $this->faker->sha256().'.pdf',
            'disk' => 'private',
            'path' => 'attachments/'.$this->faker->sha256().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1024, 1048576),
        ];
    }

    public function onMessage(TicketMessage $message): static
    {
        return $this->state(fn () => ['message_id' => $message->id, 'ticket_id' => $message->ticket_id]);
    }
}
