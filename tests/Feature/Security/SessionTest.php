<?php

namespace Tests\Feature\Security;

use App\Models\User;

class SessionTest extends SecurityTestCase
{
    public function test_password_change_rotates_the_remember_token(): void
    {
        $user = User::factory()->requester()->create([
            'password' => 'Password123!',
            'remember_token' => 'original-remember-token',
        ]);

        $this->actingAs($user)->post(route('profile.change-password.store'), [
            'current_password' => 'Password123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

        $this->assertNotSame('original-remember-token', $user->fresh()->remember_token);
    }

    public function test_admin_password_reset_rotates_the_remember_token(): void
    {
        $target = User::factory()->requester()->create(['remember_token' => 'original-remember-token']);

        $this->actingAs($this->admin)
            ->patch(route('admin.users.reset-password', $target))
            ->assertRedirect();

        $this->assertNotSame('original-remember-token', $target->fresh()->remember_token);
    }

    public function test_oversized_passwords_are_rejected(): void
    {
        $user = User::factory()->requester()->create(['password' => 'Password123!']);

        $this->actingAs($user)->post(route('profile.change-password.store'), [
            'current_password' => 'Password123!',
            'password' => str_repeat('a', 71).'Aa1',
            'password_confirmation' => str_repeat('a', 71).'Aa1',
        ])->assertSessionHasErrors('password');
    }
}
