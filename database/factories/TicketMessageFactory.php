<?php

namespace Database\Factories;

use App\Enums\MessageType;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketMessageFactory extends Factory
{
    protected $model = TicketMessage::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'type' => MessageType::Public,
            'body' => $this->faker->paragraph(3),
        ];
    }

    public function internal(): static
    {
        return $this->state(fn () => ['type' => MessageType::Internal]);
    }

    public function public(): static
    {
        return $this->state(fn () => ['type' => MessageType::Public]);
    }
}
