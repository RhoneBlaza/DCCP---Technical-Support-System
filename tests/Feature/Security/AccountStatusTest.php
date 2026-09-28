<?php

namespace Tests\Feature\Security;

use App\Models\User;

class AccountStatusTest extends SecurityTestCase
{
    public function test_login_returns_the_same_generic_error_for_unknown_email_and_wrong_password(): void
    {
        $known = User::factory()->requester()->create(['password' => 'Password123!']);

        $this->post(route('login.attempt'), [
            'email' => 'nobody.unknown@example.com',
            'password' => 'Password123!',
        ])->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->post(route('login.attempt'), [
            'email' => $known->email,
            'password' => 'TotallyWrong123!',
        ])->assertSessionHasErrors(['email' => __('auth.failed')]);
    }

    public function test_non_approved_account_with_correct_password_is_sent_to_the_status_page(): void
    {
        $pending = User::factory()->pendingApproval()->create(['password' => 'Password123!']);

        $this->post(route('login.attempt'), [
            'email' => $pending->email,
            'password' => 'Password123!',
        ])->assertRedirectContains('/account-status/'.$pending->id);
    }

    public function test_deactivated_account_cannot_log_in_with_a_correct_password(): void
    {
        $inactive = User::factory()->requester()->inactive()->create(['password' => 'Password123!']);

        $this->post(route('login.attempt'), [
            'email' => $inactive->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors(['email' => __('auth.failed')]);
    }
}
