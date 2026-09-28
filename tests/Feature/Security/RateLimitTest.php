<?php

namespace Tests\Feature\Security;

class RateLimitTest extends SecurityTestCase
{
    public function test_password_reset_endpoint_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.store'), [
                'token' => 'invalid-token',
                'email' => $this->requester->email,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);
        }

        $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $this->requester->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(429);
    }
}
