<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class SecurityTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $support;

    protected User $requester;

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
}
