<?php

namespace Tests\Feature;

use App\Enums\ThemePreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The theme has to survive three places at once: localStorage for the very first
 * paint (client side), the users.theme column so it follows an account to another
 * device, and the rendered markup, which carries the value the anti-flash script
 * reads. These cover the server side of that contract and the boundaries around it.
 */
class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_a_new_account_has_no_theme_so_it_follows_the_device(): void
    {
        $this->assertNull($this->user->fresh()->theme);
    }

    public function test_the_top_bar_toggle_saves_the_choice_to_the_account(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('profile.theme'), ['theme' => 'dark']);

        $response->assertOk()->assertJson(['theme' => 'dark']);
        $this->assertSame(ThemePreference::Dark, $this->user->fresh()->theme);
    }

    public function test_the_toggle_accepts_every_supported_preference(): void
    {
        foreach (ThemePreference::cases() as $preference) {
            $this->actingAs($this->user)
                ->postJson(route('profile.theme'), ['theme' => $preference->value])
                ->assertOk();

            $this->assertSame($preference, $this->user->fresh()->theme);
        }
    }

    public function test_the_toggle_rejects_an_unknown_preference(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('profile.theme'), ['theme' => 'neon'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('theme');

        $this->assertNull($this->user->fresh()->theme);
    }

    public function test_the_toggle_requires_a_preference(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('profile.theme'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('theme');
    }

    public function test_a_guest_cannot_reach_the_theme_endpoint(): void
    {
        $this->post(route('profile.theme'), ['theme' => 'dark'])
            ->assertRedirect(route('login'));
    }

    public function test_the_profile_form_persists_the_theme(): void
    {
        $this->actingAs($this->user)->put(route('profile.update'), [
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'email' => $this->user->email,
            'theme' => 'light',
        ])->assertRedirect();

        $this->assertSame(ThemePreference::Light, $this->user->fresh()->theme);
    }

    public function test_the_profile_form_rejects_an_unknown_theme(): void
    {
        $this->actingAs($this->user)->put(route('profile.update'), [
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'email' => $this->user->email,
            'theme' => 'neon',
        ])->assertSessionHasErrors('theme');

        $this->assertNull($this->user->fresh()->theme);
    }

    public function test_the_layout_hands_the_saved_preference_to_the_anti_flash_script(): void
    {
        $this->user->update(['theme' => 'dark']);

        $this->actingAs($this->user->fresh())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('preference = "dark";', false)
            ->assertDontSee('window.themeEndpoint = null;', false)
            // The script embeds the URL through json_encode, so slashes are escaped.
            ->assertSee('window.themeEndpoint = '.json_encode(route('profile.theme')).';', false);
    }

    public function test_a_guest_layout_defaults_to_following_the_device(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('preference = "system";', false)
            // No endpoint for a guest: the toggle only writes to localStorage.
            ->assertSee('window.themeEndpoint = null;', false);
    }

    public function test_the_preference_can_be_cleared_back_to_the_device_default(): void
    {
        $this->user->update(['theme' => 'dark']);

        $this->actingAs($this->user)->put(route('profile.update'), [
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'email' => $this->user->email,
            'theme' => null,
        ])->assertRedirect();

        $this->assertNull($this->user->fresh()->theme);
    }
}
