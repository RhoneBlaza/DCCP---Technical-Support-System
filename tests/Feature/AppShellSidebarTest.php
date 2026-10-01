<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The auto-hide drawer only works while its directives sit inside an Alpine
 * root. If the shell ever loses its x-data attribute, Alpine never walks the
 * aside and its x-cloak wins forever, which is exactly how the sidebar once
 * disappeared with no way to bring it back.
 */
class AppShellSidebarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@sample.com')->firstOrFail();
    }

    public function test_the_app_shell_exposes_an_alpine_root_for_the_drawer(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="mobile-sidebar"', $html);

        $shellStart = strpos($html, 'h-[100vh] h-[100dvh]');
        $this->assertNotFalse($shellStart, 'The app shell wrapper is missing.');

        $shell = substr($html, $shellStart, 400);
        $this->assertStringContainsString(
            'x-data',
            $shell,
            'The shell must stay an Alpine root or the drawer x-cloak never clears.'
        );
    }

    public function test_the_brand_logo_is_the_drawer_trigger(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="sidebar-brand-toggle"', $html);
        $this->assertStringContainsString('aria-controls="mobile-sidebar"', $html);
        $this->assertStringContainsString('$store.sidebar.toggleDrawer()', $html);
    }

    public function test_guest_pages_do_not_render_the_sidebar(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('id="mobile-sidebar"', false);
    }
}
