<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Str;

class OpenRedirectTest extends SecurityTestCase
{
    public function test_notification_mark_read_ignores_external_urls(): void
    {
        $external = $this->requester->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => ['text' => 'Phish', 'url' => 'https://evil.example/phish'],
            'read_at' => null,
        ]);

        $this->actingAs($this->requester)
            ->post(route('notifications.read', $external->id))
            ->assertRedirect(route('notifications.index'));

        $local = $this->requester->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => ['text' => 'Dashboard', 'url' => route('dashboard')],
            'read_at' => null,
        ]);

        $this->actingAs($this->requester)
            ->post(route('notifications.read', $local->id))
            ->assertRedirect(route('dashboard'));
    }
}
