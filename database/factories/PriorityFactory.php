<?php

namespace Database\Factories;

use App\Models\Priority;
use Database\Factories\Concerns\CyclesRealisticNames;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Priorities are a user-facing lookup table, so fixtures use real support
 * vocabulary rather than faker's Lorem Ipsum word list, which the application
 * now rejects through App\Rules\NotPlaceholderText.
 */
class PriorityFactory extends Factory
{
    use CyclesRealisticNames;

    protected $model = Priority::class;

    /**
     * @var list<string>
     */
    protected static array $names = [
        'Jumbo', 'Rush', 'Minor', 'Major', 'Escalated', 'Deferred', 'Scheduled',
        'Immediate', 'Routine', 'Planned', 'Elevated', 'Standard', 'Severe', 'Moderate',
    ];

    public function definition(): array
    {
        $name = static::nextRealisticName(static::$names);

        return [
            'key' => str($name)->slug('_')->value(),
            'name' => $name,
            'description' => $this->faker->optional()->sentence(),
            'sla_hours' => $this->faker->numberBetween(4, 72),
            // Starts above the seeded system tiers (1-4) so factory data can never
            // collide with the unique level constraint on the reference priorities.
            'level' => static::nextUniqueNumber(5, 100),
            'is_requester_selectable' => true,
            'is_active' => true,
            'is_system' => false,
        ];
    }
}
