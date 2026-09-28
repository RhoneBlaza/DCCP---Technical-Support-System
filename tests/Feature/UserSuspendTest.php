<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSuspendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_suspend_user()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'account_status' => AccountStatus::Approved,
            'is_active' => true,
        ]);

        // Create a regular user (non-admin)
        $user = User::factory()->create([
            'role' => UserRole::Requester->value,
            'account_status' => AccountStatus::Approved,
            'is_active' => true,
        ]);

        // Act as admin
        $this->actingAs($admin);

        // Visit the admin users index to set previous page
        $this->get(route('admin.users.index'));

        // Get initial status
        $this->assertEquals(AccountStatus::Approved, $user->fresh()->account_status);

        // Send suspend request
        $response = $this->patch(route('admin.users.toggle-suspend', $user));

        // Redirect back with success status
        $response->assertRedirectBack();
        $response->assertSessionHas('status', 'User suspended successfully.');

        // Check that user is now suspended
        $this->assertEquals(AccountStatus::Suspended, $user->fresh()->account_status);
    }

    public function test_admin_can_reactivate_user()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'account_status' => AccountStatus::Approved,
            'is_active' => true,
        ]);

        // Create a regular user that is suspended
        $user = User::factory()->create([
            'role' => UserRole::Requester->value,
            'account_status' => AccountStatus::Suspended,
            'is_active' => true,
        ]);

        // Act as admin
        $this->actingAs($admin);

        // Visit the admin users index to set previous page
        $this->get(route('admin.users.index'));

        // Get initial status
        $this->assertEquals(AccountStatus::Suspended, $user->fresh()->account_status);

        // Send reactivate request
        $response = $this->patch(route('admin.users.toggle-suspend', $user));

        // Redirect back with success status
        $response->assertRedirectBack();
        $response->assertSessionHas('status', 'User reactivated successfully.');

        // Check that user is now approved
        $this->assertEquals(AccountStatus::Approved, $user->fresh()->account_status);
    }

    public function test_admin_cannot_suspend_themselves()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'account_status' => AccountStatus::Approved,
            'is_active' => true,
        ]);

        // Act as admin
        $this->actingAs($admin);

        // Send suspend request on oneself
        $response = $this->patch(route('admin.users.toggle-suspend', $admin));

        // Redirect back with error
        $response->assertRedirectBack();
        $response->assertSessionHasErrors('error');
    }

    public function test_admin_cannot_delete_themselves()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'account_status' => AccountStatus::Approved,
            'is_active' => true,
        ]);

        // Act as admin
        $this->actingAs($admin);

        // Send delete request on oneself
        $response = $this->delete(route('admin.users.destroy', $admin));

        // Redirect back with error
        $response->assertRedirectBack();
        $response->assertSessionHasErrors('error');
    }

    public function test_admin_cannot_delete_last_admin()
    {
        // Create only one admin user
        $admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'account_status' => AccountStatus::Approved,
            'is_active' => true,
        ]);

        // Create a regular user
        $user = User::factory()->create([
            'role' => UserRole::Requester->value,
            'account_status' => AccountStatus::Approved,
            'is_active' => true,
        ]);

        // Act as admin
        $this->actingAs($admin);

        // Attempt to delete the only admin
        $response = $this->delete(route('admin.users.destroy', $admin));

        // Redirect back with error
        $response->assertRedirectBack();
        $response->assertSessionHasErrors('error');

        // Ensure admin still exists
        $this->assertNotNull(User::find($admin->id));
    }

    public function test_admin_cannot_delete_user_with_open_tickets()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'account_status' => AccountStatus::Approved,
            'is_active' => true,
        ]);

        // Create a regular user with an open ticket
        $user = User::factory()->create([
            'role' => UserRole::Requester->value,
            'account_status' => AccountStatus::Approved,
            'is_active' => true,
        ]);

        // Get the open status
        $openStatus = TicketStatus::where('type', 'open')->first();
        if (! $openStatus) {
            $openStatus = TicketStatus::create([
                'key' => 'open',
                'name' => 'Open',
                'color' => 'gray',
                'sort_order' => 1,
                'type' => 'open',
                'pauses_sla' => false,
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // Create an open ticket assigned to the user
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
            'assigned_to' => $user->id,
        ]);

        // Act as admin
        $this->actingAs($admin);

        // Attempt to delete the user with open ticket
        $response = $this->delete(route('admin.users.destroy', $user));

        // Redirect back with error
        $response->assertRedirectBack();
        $response->assertSessionHasErrors('error');

        // Ensure user still exists
        $this->assertNotNull(User::find($user->id));
    }
}
