<?php

namespace Database\Factories;

use App\Models\Department;
use Database\Factories\Concerns\CyclesRealisticNames;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Departments are a user-facing lookup table, so fixtures use real campus unit
 * names rather than faker company names, which read as filler in rendered test
 * output and clash with App\Rules\NotPlaceholderText.
 */
class DepartmentFactory extends Factory
{
    use CyclesRealisticNames;

    protected $model = Department::class;

    /**
     * @var list<string>
     */
    protected static array $names = [
        'Administration Office', 'Registrar Office', 'Business Affairs Office',
        'Library Services', 'Research Office', 'Guidance Office',
        'Facilities Office', 'Student Services Office', 'Cashier Office',
        'Human Resource Office', 'Planning Office', 'Supply Office',
        'Extension Services Office', 'Accounting Office',
    ];

    public function definition(): array
    {
        return [
            'name' => static::nextRealisticName(static::$names),
            'code' => static::nextCode(),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
