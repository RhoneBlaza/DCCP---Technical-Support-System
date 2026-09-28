<?php

namespace Database\Factories;

use App\Enums\TicketStatusType;
use App\Models\TicketStatus;
use Database\Factories\Concerns\CyclesRealisticNames;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Ticket statuses are a user-facing lookup table, so fixtures use real support
 * vocabulary rather than faker's Lorem Ipsum word list, which the application
 * now rejects through App\Rules\NotPlaceholderText.
 */
class TicketStatusFactory extends Factory
{
    use CyclesRealisticNames;

    protected $model = TicketStatus::class;

    /**
     * @var list<string>
     */
    protected static array $names = [
        'Awaiting Parts', 'Awaiting Site Access', 'Awaiting Vendor', 'On Hold',
        'Under Review', 'Scheduled Visit', 'Awaiting Approval', 'Transferred',
        'Remote Session', 'Backlog', 'Ready to Start', 'Blocked', 'Monitoring',
        'Needs Info', 'Duplicate Review', 'Awaiting Budget',
    ];

    public function definition(): array
    {
        $type = $this->faker->randomElement(TicketStatusType::cases());
        $name = static::nextRealisticName(static::$names);

        return [
            'key' => str($name)->slug('_')->value(),
            'name' => $name,
            'color' => $this->faker->randomElement(config('tsts.status_colors')),
            // Starts above the seeded system statuses so factory data can never
            // collide with the unique sort order constraint on the table.
            'sort_order' => static::nextUniqueNumber(9, 100),
            'type' => $type,
            'pauses_sla' => $type === TicketStatusType::Pending,
            'is_system' => false,
            'is_active' => true,
        ];
    }

    public function ofType(TicketStatusType $type): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'pauses_sla' => $type === TicketStatusType::Pending,
        ]);
    }
}
