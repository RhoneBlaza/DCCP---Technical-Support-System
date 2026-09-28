<?php

namespace Database\Factories;

use App\Enums\TicketStatusType;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $requester = User::factory()->requester()->create();
        $status = TicketStatus::factory()->ofType(TicketStatusType::Open)->create();

        return [
            'ticket_number' => strtoupper('TST-'.$this->faker->unique()->numerify('######')),
            'requester_id' => $requester->id,
            'created_by' => $requester->id,
            'department_id' => Department::factory(),
            'category_id' => Category::factory(),
            'priority_id' => Priority::factory(),
            'status_id' => $status->id,
            'assigned_to' => null,
            'subject' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'location' => $this->faker->optional()->address(),
            'device_type' => $this->faker->optional()->randomElement(['Desktop', 'Laptop', 'Printer', 'Other']),
            'asset_number' => $this->faker->optional()->numerify('AST-####'),
            'contact_number' => $this->faker->optional()->phoneNumber(),
            'due_at' => now()->addHours(48),
            'sla_paused_at' => null,
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
            'reopen_count' => 0,
            'overdue_notified_at' => null,
        ];
    }

    public function withStatus(TicketStatus $status): static
    {
        return $this->state(fn () => ['status_id' => $status->id]);
    }

    public function assignedTo(User $staff): static
    {
        return $this->state(fn () => ['assigned_to' => $staff->id]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['due_at' => now()->subHour()]);
    }

    public function notOverdue(): static
    {
        return $this->state(fn () => ['due_at' => now()->addHours(48)]);
    }
}
