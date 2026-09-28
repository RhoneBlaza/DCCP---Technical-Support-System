<?php

namespace Tests\Feature\Security;

use App\Models\Category;

class SqlInjectionTest extends SecurityTestCase
{
    public function test_underscore_like_wildcards_are_escaped(): void
    {
        Category::create([
            'name' => '10001 Prepaid Plans',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.categories.index', ['search' => '100_1']))
            ->assertOk()
            ->assertDontSee('10001 Prepaid Plans');
    }
}
