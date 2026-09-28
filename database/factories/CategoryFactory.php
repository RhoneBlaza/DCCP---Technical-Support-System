<?php

namespace Database\Factories;

use App\Models\Category;
use Database\Factories\Concerns\CyclesRealisticNames;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Categories are a user-facing lookup table, so fixtures use real support
 * vocabulary rather than faker's Lorem Ipsum word list, which makes rendered
 * test output unreadable and clashes with App\Rules\NotPlaceholderText.
 */
class CategoryFactory extends Factory
{
    use CyclesRealisticNames;

    protected $model = Category::class;

    /**
     * @var list<string>
     */
    protected static array $names = [
        'Telephone Line', 'Television', 'Air Conditioning', 'Power Supply',
        'Laboratory Equipment', 'Audio System', 'Badge Reader', 'Document Scanner',
        'Conference Room', 'Attendance Kiosk',
    ];

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name' => static::nextRealisticName(static::$names),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function parent(): static
    {
        return $this->state(['parent_id' => null]);
    }

    public function child(Category $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
