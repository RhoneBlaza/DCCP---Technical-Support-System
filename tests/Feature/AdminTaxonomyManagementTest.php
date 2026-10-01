<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Departments and categories gained a Delete action in the admin list; these
 * tests pin the guard rails around it and the admin-only policy.
 */
class AdminTaxonomyManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $support;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@sample.com')->firstOrFail();
        $this->support = User::where('email', 'support@sample.com')->firstOrFail();
    }

    public function test_an_admin_can_delete_an_unused_department(): void
    {
        $department = Department::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.departments.destroy', $department))
            ->assertRedirect(route('admin.departments.index'));

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
        $this->assertTrue(
            AuditLog::where('action', 'department_deleted')->where('auditable_id', $department->id)->exists()
        );
    }

    public function test_a_department_with_users_cannot_be_deleted(): void
    {
        $department = Department::factory()->create();
        User::factory()->requester()->create(['department_id' => $department->id]);

        $this->actingAs($this->admin)
            ->from(route('admin.departments.index'))
            ->delete(route('admin.departments.destroy', $department))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_a_department_can_be_toggled_active(): void
    {
        $department = Department::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->patch(route('admin.departments.toggle-active', $department))
            ->assertRedirect();

        $this->assertFalse($department->fresh()->is_active);
    }

    public function test_an_admin_can_delete_an_unused_leaf_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_a_category_with_children_cannot_be_deleted(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->child($parent)->create();

        $this->actingAs($this->admin)
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $parent))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_support_cannot_delete_taxonomy(): void
    {
        $department = Department::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($this->support)
            ->delete(route('admin.departments.destroy', $department))
            ->assertForbidden();

        $this->actingAs($this->support)
            ->delete(route('admin.categories.destroy', $category))
            ->assertForbidden();

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
