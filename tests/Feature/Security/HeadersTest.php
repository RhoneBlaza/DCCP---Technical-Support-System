<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class HeadersTest extends SecurityTestCase
{
    public function test_guest_responses_are_marked_no_store(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_account_status_page_is_no_store_even_for_guests(): void
    {
        $pending = User::factory()->pendingApproval()->create();

        $url = URL::signedRoute('account.status', ['user' => $pending->id]);

        $this->get($url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_hsts_header_is_only_sent_in_production_over_https(): void
    {
        $middleware = new SecurityHeaders;

        config(['app.env' => 'local']);

        $insecure = $middleware->handle(
            Request::create('/login', 'GET'),
            fn () => new Response('ok')
        );

        $this->assertFalse($insecure->headers->has('Strict-Transport-Security'));

        config(['app.env' => 'production']);

        $secure = $middleware->handle(
            Request::create('/login', 'GET', [], [], [], ['HTTPS' => 'on']),
            fn () => new Response('ok')
        );

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $secure->headers->get('Strict-Transport-Security')
        );
    }
}
