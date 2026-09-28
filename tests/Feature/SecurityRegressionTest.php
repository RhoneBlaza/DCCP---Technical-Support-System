<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\ActivityType;
use App\Enums\MessageType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $support;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@dccp-bangued.test')->firstOrFail();
        $this->support = User::where('email', 'support@dccp-bangued.test')->firstOrFail();
        $this->requester = User::where('email', 'juan@dccp-bangued.test')->firstOrFail();

        Storage::fake('private');
        Notification::fake();
    }

    public function test_password_reset_does_not_reveal_whether_an_email_exists(): void
    {
        $existing = $this->post(route('password.email'), ['email' => $this->requester->email]);
        $unknown = $this->post(route('password.email'), ['email' => 'ghost.unknown@example.com']);

        $existing->assertRedirect()->assertSessionHasNoErrors();
        $unknown->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(session('status'), __('passwords.sent'));
    }

    public function test_requester_never_sees_internal_activities_on_their_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'requester_id' => $this->requester->id,
            'created_by' => $this->requester->id,
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->support->id,
            'type' => ActivityType::SupportReply->value,
            'description' => 'Support replied to the request',
            'is_internal' => false,
            'created_at' => now(),
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->support->id,
            'type' => ActivityType::InternalNote->value,
            'description' => 'Internal coordination note',
            'is_internal' => true,
            'created_at' => now()->addSecond(),
        ]);

        $this->actingAs($this->requester)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Support replied to the request')
            ->assertDontSee('Internal coordination note');

        $this->actingAs($this->support)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Internal coordination note');
    }

    public function test_reports_export_escapes_spreadsheet_formula_injection(): void
    {
        Ticket::factory()->create([
            'subject' => '=HYPERLINK("http://evil.example")',
            'description' => str_repeat('x', 10),
        ]);

        $response = $this->actingAs($this->support)->get(route('reports.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('\'=HYPERLINK', $content);
    }

    public function test_category_search_escapes_like_wildcard_characters(): void
    {
        Category::create([
            'name' => '10001 Prepaid Plans',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.categories.index', ['search' => '100%']))
            ->assertOk()
            ->assertDontSee('10001 Prepaid Plans');
    }

    public function test_security_headers_are_sent_on_guest_and_authenticated_pages(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $this->actingAs($this->requester)
            ->get(route('dashboard'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_authenticated_responses_are_marked_no_store(): void
    {
        $this->actingAs($this->requester)
            ->get(route('dashboard'))
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_login_endpoint_throttles_brute_force_attempts(): void
    {
        $user = User::factory()->requester()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'WrongPassword!',
            ])->assertSessionHasErrors('email');
        }

        $throttled = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'WrongPassword!',
        ]);

        $throttled->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->get('email')[0]);
    }

    public function test_all_state_changing_routes_are_within_the_csrf_protected_web_group(): void
    {
        foreach (Route::getRoutes() as $route) {
            $methods = array_values(array_diff($route->methods(), ['HEAD', 'GET', 'OPTIONS']));

            if ($methods === []) {
                continue;
            }

            $this->assertContains(
                'web',
                $route->gatherMiddleware(),
                "State-changing route [{$route->uri()}] is outside the web group and not CSRF protected."
            );
        }
    }

    public function test_framework_storage_serve_routes_are_not_registered(): void
    {
        foreach (Route::getRoutes() as $route) {
            $this->assertStringNotContainsString(
                'storage/',
                $route->uri(),
                "Framework storage route [{$route->uri()}] should not be exposed."
            );
        }
    }

    public function test_error_pages_do_not_leak_exception_details_when_debug_is_disabled(): void
    {
        Route::get('/_security/boom', fn () => throw new \RuntimeException('Internal secret detail 5f3a91'));

        config(['app.debug' => false]);

        $this->get('/_security/boom')
            ->assertStatus(500)
            ->assertDontSee('Internal secret detail');
    }

    public function test_profile_update_cannot_be_abused_to_escalate_privileges(): void
    {
        $this->actingAs($this->requester)
            ->put(route('profile.update'), [
                'first_name' => 'Juan',
                'last_name' => 'dela Cruz',
                'email' => $this->requester->email,
                'role' => 'admin',
                'is_active' => 0,
                'account_status' => 'rejected',
                'must_change_password' => 1,
            ])->assertSessionHasNoErrors();

        $this->requester->refresh();

        $this->assertEquals(UserRole::Requester, $this->requester->role);
        $this->assertTrue($this->requester->is_active);
        $this->assertEquals(AccountStatus::Approved, $this->requester->account_status);
        $this->assertFalse($this->requester->must_change_password);
    }

    public function test_requester_cannot_download_attachments_on_internal_notes(): void
    {
        $ticket = Ticket::factory()->create([
            'requester_id' => $this->requester->id,
            'created_by' => $this->requester->id,
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->support->id,
            'type' => MessageType::Internal->value,
            'body' => 'Internal draft',
        ]);

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'message_id' => $message->id,
            'uploaded_by' => $this->support->id,
            'original_name' => 'notes.txt',
            'stored_name' => 'notes.txt',
            'disk' => 'private',
            'path' => 'attachments/'.$ticket->id.'/notes.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
        ]);

        Storage::disk('private')->put($attachment->path, 'hello');

        $this->actingAs($this->requester)
            ->get(route('tickets.download', [$ticket, $attachment]))
            ->assertForbidden();

        $this->actingAs($this->support)
            ->get(route('tickets.download', [$ticket, $attachment]))
            ->assertOk();
    }
}
