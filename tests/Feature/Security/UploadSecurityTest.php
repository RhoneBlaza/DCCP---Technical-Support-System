<?php

namespace Tests\Feature\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadSecurityTest extends SecurityTestCase
{
    public function test_profile_photo_is_stored_on_the_private_disk(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $this->actingAs($this->requester)
            ->put(route('profile.update'), [
                'first_name' => $this->requester->first_name,
                'last_name' => $this->requester->last_name,
                'email' => $this->requester->email,
                'photo' => UploadedFile::fake()->create('avatar.jpg', 10, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();

        $path = $this->requester->fresh()->profile_photo_path;

        $this->assertNotNull($path);
        Storage::disk('private')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }
}
