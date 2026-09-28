<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketActivityFactory extends Factory
{
    protected $model = TicketActivity::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'type' => ActivityType::Created,
            'description' => $this->faker->sentence(),
            'old_value' => null,
            'new_value' => null,
            'is_internal' => false,
            'created_at' => now(),
        ];
    }
}
