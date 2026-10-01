<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The organization name is the one sidebar string that is genuinely long and
 * genuinely variable, so it is the one that has to wrap instead of clipping.
 */
class SidebarOrganizationNameTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@sample.com')->firstOrFail();
    }

    public function test_a_long_organization_name_wraps_instead_of_being_clipped(): void
    {
        $name = 'Data Center College of the Philippines - Bangued Campus Institute of Computer Studies';

        (new SettingsService)->set('organization_name', $name);

        $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString($name, $html);
        $this->assertStringNotContainsString(
            'text-[11px] text-slate-300 leading-tight truncate',
            $html,
            'The organization name must never be truncated with an ellipsis.'
        );
        $this->assertStringNotContainsString(
            'text-[11px] text-slate-300 leading-tight line-clamp-2',
            $html,
            'The organization name must never be clamped to a fixed number of lines.'
        );
        $this->assertMatchesRegularExpression(
            '/text-\[11px\] text-slate-300 leading-tight break-words/',
            $html
        );
    }

    public function test_an_unbroken_organization_name_breaks_inside_the_sidebar(): void
    {
        (new SettingsService)->set(
            'organization_name',
            'Supercalifragilisticexpialidocious'.str_repeat('VeryLongSegment', 4)
        );

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('break-words', false);
    }
}
