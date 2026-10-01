<?php

namespace Tests\Feature;

use App\Console\Commands\PurgeExpiredIdImages;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Notifications\NewRegistrationPending;
use App\Notifications\VerificationApprovedNotification;
use App\Notifications\VerificationRejectedNotification;
use App\Notifications\VerificationResubmissionRequestedNotification;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationAndVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $support;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@sample.com')->firstOrFail();
        $this->support = User::where('email', 'support@sample.com')->firstOrFail();

        Storage::fake('private');
        Notification::fake();
    }

    protected function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'id_type' => 'school_id',
            'id_number' => 'ID-2026-0001',
            'id_image' => $this->fakeIdDocumentImage(),
            'first_name' => 'María',
            'middle_name' => 'Cruz',
            'last_name' => 'Santos',
            'email' => 'maria.santos@example.com',
            'contact_number' => '09171234567',
            'department_id' => null,
            'position' => 'Faculty',
            'password' => 'Str0ngPass!9',
            'password_confirmation' => 'Str0ngPass!9',
            'privacy_consent' => '1',
        ], $overrides);
    }

    /**
     * A tiny, valid PNG (GD is not installed, so UploadedFile::fake()->image() is unavailable).
     */
    protected function fakeIdDocumentImage(string $name = 'id-card.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    protected function signedStatusUrl(User $user, ?Carbon $expires = null): string
    {
        return URL::temporarySignedRoute('account.status', $expires ?? now()->addMinutes(15), ['user' => $user->id]);
    }

    protected function madeVerification(User $user, string $status = 'pending', ?string $path = null, ?string $idNumber = null): VerificationRequest
    {
        if ($path !== null) {
            Storage::disk('private')->put($path, 'id-document-image');
        }

        return VerificationRequest::create([
            'user_id' => $user->id,
            'id_type' => 'school_id',
            'id_number' => $idNumber ?? $user->employee_id ?? 'ID-'.$user->id,
            'id_image_path' => $path,
            'status' => $status,
            'submitted_at' => now(),
            'reviewed_by' => $status === 'pending' ? null : $this->admin->id,
            'reviewed_at' => $status === 'pending' ? null : now(),
            'decision_note' => $status === 'rejected' ? 'Unreadable ID copy' : null,
        ]);
    }

    public function test_registration_creates_a_pending_requester_with_a_private_id_document(): void
    {
        $department = Department::firstOrFail();

        $response = $this->post(route('register.attempt'), $this->registrationPayload([
            'department_id' => $department->id,
        ]));

        $response->assertRedirect(route('login'));

        $user = User::where('email', 'maria.santos@example.com')->firstOrFail();

        $this->assertEquals(UserRole::Requester, $user->role);
        $this->assertEquals(AccountStatus::Pending, $user->account_status);
        $this->assertFalse($user->is_active);
        $this->assertSame('ID-2026-0001', $user->employee_id);
        $this->assertSame('ID-2026-0001', $user->verificationRequests()->first()->id_number);

        $imagePath = $user->verificationRequests()->first()->id_image_path;
        $this->assertNotNull($imagePath);
        Storage::disk('private')->assertExists($imagePath);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'registration_requested',
            'user_id' => $user->id,
        ]);

        Notification::assertSentTo($this->admin, NewRegistrationPending::class);
    }

    public function test_registration_rejects_an_injected_role_field(): void
    {
        $department = Department::firstOrFail();

        $response = $this->post(route('register.attempt'), $this->registrationPayload([
            'id_number' => 'ID-2026-0002',
            'email' => 'injector@example.com',
            'department_id' => $department->id,
            'role' => 'admin',
        ]));

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'injector@example.com']);
    }

    public function test_pending_user_cannot_sign_in_and_is_sent_to_the_status_page(): void
    {
        $user = User::factory()->requester()->pendingApproval()->create();

        $response = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertStringStartsWith(route('account.status', $user).'?', $response->headers->get('Location'));
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'login_blocked']);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('awaiting administrator approval');
    }

    public function test_rejected_user_cannot_sign_in_and_sees_the_decision_note(): void
    {
        $user = User::factory()->requester()->rejected()->create();
        $this->madeVerification($user, 'rejected', 'verifications/old.png');

        $blocked = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $blocked->assertRedirect();
        $this->assertGuest();

        $this->get($blocked->headers->get('Location'))
            ->assertOk()
            ->assertSee('not approved')
            ->assertSee('Unreadable ID copy');
    }

    public function test_suspended_user_cannot_sign_in_and_sees_the_suspension_page(): void
    {
        $user = User::factory()->requester()->create(['account_status' => AccountStatus::Suspended]);

        $blocked = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertRedirect();

        $this->assertGuest();

        $this->get($blocked->headers->get('Location'))
            ->assertOk()
            ->assertSee('suspended');
    }

    public function test_wrong_password_still_returns_a_generic_login_error(): void
    {
        $user = User::factory()->requester()->pendingApproval()->create();

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'WrongPassword!',
        ])->assertSessionHasErrors('email');
    }

    public function test_null_status_is_treated_as_pending_and_never_crashes(): void
    {
        $user = User::factory()->requester()->create([
            'account_status' => null,
            'is_active' => false,
        ]);

        $user->refresh();
        $this->assertEquals(AccountStatus::Pending, $user->account_status);
        $this->assertFalse($user->isApproved());
        $this->assertTrue($user->isPendingApproval());
        $this->assertSame('Pending approval', $user->account_status->label());

        $user->account_status = null;
        $this->assertFalse($user->isApproved());
        $this->assertSame('Pending approval', $user->account_status->label());

        $blocked = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertRedirect();

        $this->assertGuest();
        $this->get($blocked->headers->get('Location'))
            ->assertOk()
            ->assertSee('awaiting administrator approval');
    }

    public function test_account_status_page_cannot_be_opened_by_guessing_or_changing_the_id(): void
    {
        $pending = User::factory()->requester()->pendingApproval()->create();
        $other = User::factory()->requester()->create(['account_status' => AccountStatus::Rejected]);

        $this->get(route('account.status', $pending))->assertForbidden();

        $replaced = URL::temporarySignedRoute('account.status', now()->addMinutes(15), ['user' => $other->id]);

        $replaced = preg_replace('#/account-status/'.$other->id.'#', '/account-status/'.$pending->id, $replaced);
        $this->get($replaced)->assertForbidden();

        $unsigned = preg_replace('#([?&])signature=[^&]*#', '', $this->signedStatusUrl($pending));
        $this->get($unsigned)->assertForbidden();

        $expired = URL::temporarySignedRoute('account.status', now()->subMinute(), ['user' => $pending->id]);
        $this->get($expired)->assertForbidden();

        $this->get($this->signedStatusUrl($pending))
            ->assertOk()
            ->assertSee('awaiting administrator approval');
    }

    public function test_seeded_demo_users_can_log_in(): void
    {
        foreach (['admin@sample.com', 'support@sample.com', 'requester@sample.com'] as $email) {
            $user = User::where('email', $email)->firstOrFail();

            $this->post(route('login.attempt'), [
                'email' => $email,
                'password' => 'Admin123',
            ])->assertRedirect(route('dashboard'));

            $this->assertAuthenticatedAs($user);

            $this->post(route('logout'));
            $this->assertGuest();
        }
    }

    public function test_only_admins_can_manage_verifications(): void
    {
        $applicant = User::factory()->requester()->pendingApproval()->create();
        $verification = $this->madeVerification($applicant, 'pending', 'verifications/current.png');

        $this->actingAs($this->support)->get(route('admin.verifications.index'))->assertForbidden();
        $this->actingAs($this->support)->get(route('admin.verifications.show', $verification))->assertForbidden();
        $this->actingAs($this->support)->patch(route('admin.verifications.approve', $verification))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.verifications.index'))->assertOk()->assertSee('ID verifications');
        $this->actingAs($this->admin)->get(route('admin.verifications.show', $verification))
            ->assertOk()
            ->assertSee($applicant->full_name);
    }

    public function test_admin_can_approve_a_verification_and_the_user_can_sign_in(): void
    {
        $applicant = User::factory()->requester()->pendingApproval()->create();
        $verification = $this->madeVerification($applicant, 'pending', 'verifications/current.png');

        $this->actingAs($this->admin)
            ->patch(route('admin.verifications.approve', $verification))
            ->assertRedirect(route('admin.verifications.show', $verification));

        $applicant->refresh();
        $verification->refresh();

        $this->assertSame('approved', $verification->status);
        $this->assertEquals(AccountStatus::Approved, $applicant->account_status);
        $this->assertTrue($applicant->is_active);
        $this->assertSame($this->admin->id, $verification->reviewed_by);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'registration_approved',
            'auditable_id' => $applicant->id,
        ]);

        Notification::assertSentTo($applicant, VerificationApprovedNotification::class);

        $this->post(route('login.attempt'), [
            'email' => $applicant->email,
            'password' => 'Password123!',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_reject_requiring_a_reason_and_it_notifies_the_user(): void
    {
        $applicant = User::factory()->requester()->pendingApproval()->create();
        $verification = $this->madeVerification($applicant, 'pending', 'verifications/current.png');

        $this->actingAs($this->admin)
            ->patch(route('admin.verifications.reject', $verification))
            ->assertSessionHasErrors('decision_note');

        $this->actingAs($this->admin)->patch(route('admin.verifications.reject', $verification), [
            'decision_note' => 'Please upload a clearer copy.',
        ])->assertRedirect(route('admin.verifications.show', $verification));

        $applicant->refresh();
        $verification->refresh();

        $this->assertSame('rejected', $verification->status);
        $this->assertEquals(AccountStatus::Rejected, $applicant->account_status);

        Notification::assertSentTo($applicant, VerificationRejectedNotification::class);
    }

    public function test_admin_can_request_resubmission_and_notifies_the_user(): void
    {
        $applicant = User::factory()->requester()->pendingApproval()->create();
        $verification = $this->madeVerification($applicant, 'pending', 'verifications/current.png');

        $this->actingAs($this->admin)->patch(route('admin.verifications.resubmit', $verification), [
            'decision_note' => 'The ID number is cut off.',
        ])->assertRedirect(route('admin.verifications.show', $verification));

        $verification->refresh();

        $this->assertSame('resubmit_requested', $verification->status);
        $this->assertEquals(AccountStatus::Pending, $verification->user->account_status);

        Notification::assertSentTo($applicant, VerificationResubmissionRequestedNotification::class);
    }

    public function test_only_admins_can_view_the_id_image_and_every_view_is_logged(): void
    {
        $applicant = User::factory()->requester()->pendingApproval()->create(['employee_id' => 'ID-2026-0099']);
        $verification = $this->madeVerification($applicant, 'pending', 'verifications/scan.png');

        $this->actingAs($this->support)->get(route('admin.verifications.image', $verification))->assertForbidden();

        $response = $this->actingAs($this->admin)->get(route('admin.verifications.image', $verification));

        $response->assertOk();
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsStringIgnoringCase('no-store', $response->headers->get('Cache-Control') ?? '');
        $this->assertSame('image/png', $response->headers->get('Content-Type'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'id_image_viewed',
            'auditable_type' => VerificationRequest::class,
            'auditable_id' => $verification->id,
        ]);
    }

    public function test_id_image_returns_404_when_the_file_is_missing(): void
    {
        $applicant = User::factory()->requester()->pendingApproval()->create();
        $verification = $this->madeVerification($applicant, 'pending');

        $this->actingAs($this->admin)
            ->get(route('admin.verifications.image', $verification))
            ->assertNotFound();
    }

    public function test_show_page_warns_about_a_duplicate_employee_id(): void
    {
        $applicant = User::factory()->requester()->pendingApproval()->create(['employee_id' => 'ID-2026-0101']);
        $verification = $this->madeVerification($applicant, 'pending', 'verifications/a.png', 'ID-2026-0100');

        User::factory()->requester()->create(['employee_id' => 'ID-2026-0100']);

        $this->actingAs($this->admin)
            ->get(route('admin.verifications.show', $verification))
            ->assertOk()
            ->assertSee('matches another user');
    }

    public function test_a_rejected_user_can_resubmit_and_keeps_a_full_history(): void
    {
        $rejected = User::factory()->requester()->rejected()->create(['employee_id' => 'ID-2026-0200']);
        $original = $this->madeVerification($rejected, 'rejected', 'verifications/old.png');

        $department = Department::firstOrFail();

        $response = $this->post(route('register.attempt'), $this->registrationPayload([
            'id_number' => 'ID-2026-0200',
            'email' => $rejected->email,
            'department_id' => $department->id,
            'password' => 'Resubmitted!1',
            'password_confirmation' => 'Resubmitted!1',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasNoErrors();

        $this->assertSame(2, $rejected->verificationRequests()->count());

        $latest = $rejected->verificationRequests()->first();
        $this->assertSame('pending', $latest->status);
        $this->assertNotSame($original->id, $latest->id);
        $this->assertNotNull($latest->id_image_path);
        Storage::disk('private')->assertExists($latest->id_image_path);
        Storage::disk('private')->assertExists('verifications/old.png'); // history kept

        $this->assertEquals(AccountStatus::Pending, $rejected->fresh()->account_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'registration_resubmitted']);
    }

    public function test_an_approved_identity_cannot_be_registered_again(): void
    {
        $approved = User::factory()->requester()->create(['employee_id' => 'ID-2026-0300']);
        $department = Department::firstOrFail();

        $this->post(route('register.attempt'), $this->registrationPayload([
            'id_number' => 'ID-2026-0300',
            'email' => 'someone.else@example.com',
            'department_id' => $department->id,
        ]))->assertSessionHasErrors('id_number');
    }

    public function test_admin_created_users_are_pre_approved(): void
    {
        $department = Department::firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'employee_id' => 'EMP-0100',
            'first_name' => 'Paolo',
            'last_name' => 'Garcia',
            'email' => 'paolo.garcia@example.com',
            'contact_number' => '09999999999',
            'department_id' => $department->id,
            'position' => 'IT Staff',
            'role' => 'support',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'paolo.garcia@example.com')->firstOrFail();

        $this->assertEquals(AccountStatus::Approved, $user->account_status);
        $this->assertTrue($user->is_active);
    }

    public function test_purge_command_deletes_only_expired_id_documents_and_keeps_the_record(): void
    {
        app(SettingsService::class)->set('id_image_retention_days', 30, 'integer');

        $expiredUser = User::factory()->requester()->create();
        $expired = $this->madeVerification($expiredUser, 'approved', 'verifications/expired.png', 'ID-2026-0501');
        $expired->forceFill([
            'submitted_at' => now()->subDays(60),
            'reviewed_at' => now()->subDays(55),
        ])->save();

        $recentUser = User::factory()->requester()->create();
        $recent = $this->madeVerification($recentUser, 'approved', 'verifications/recent.png', 'ID-2026-0502');
        $recent->forceFill(['reviewed_at' => now()->subDays(2)])->save();

        $this->artisan(PurgeExpiredIdImages::class)->assertSuccessful();

        Storage::disk('private')->assertMissing('verifications/expired.png');
        Storage::disk('private')->assertExists('verifications/recent.png');

        $this->assertNull($expired->fresh()->id_image_path);
        $this->assertSame('approved', $expired->fresh()->status);
        $this->assertSame('ID-2026-0501', $expired->fresh()->id_number);
        $this->assertNotNull($recent->fresh()->id_image_path);
    }

    public function test_purge_command_is_a_noop_when_retention_is_zero(): void
    {
        $user = User::factory()->requester()->create();
        $verification = $this->madeVerification($user, 'approved', 'verifications/keep.png');
        $verification->forceFill(['reviewed_at' => now()->subYears(2)])->save();

        $this->artisan(PurgeExpiredIdImages::class)->assertSuccessful();

        Storage::disk('private')->assertExists('verifications/keep.png');
        $this->assertNotNull($verification->fresh()->id_image_path);
    }

    public function test_activity_monitor_is_admin_only_and_renders_live_rows(): void
    {
        $this->actingAs($this->support)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($this->support)->get(route('admin.audit-logs.feed'))->assertForbidden();
        $this->actingAs($this->support)->get(route('admin.audit-logs.export'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('Activity monitor')
            ->assertSee('audit-logs/feed');

        $this->actingAs($this->admin)->get(route('admin.audit-logs.feed'))
            ->assertOk();

        AuditLog::create([
            'action' => 'ticket_created',
            'description' => 'New ticket opened',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
        ]);

        $this->actingAs($this->admin)->get(route('admin.audit-logs.feed'))
            ->assertOk()
            ->assertSee('ticket_created');
    }

    public function test_activity_monitor_csv_export_escapes_formula_inputs(): void
    {
        AuditLog::create([
            'action' => 'ticket_created',
            'description' => '=HYPERLINK("http://evil.example")',
            'ip_address' => '=1+1',
            'user_agent' => 'phpunit',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.audit-logs.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('\'=HYPERLINK', $content);
        $this->assertStringContainsString('\'=1+1', $content);
    }
}
