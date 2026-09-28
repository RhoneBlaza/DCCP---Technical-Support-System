<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Priority;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use App\Support\MailConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settings,
        protected AuditLogger $audit,
    ) {}

    public function edit(): View
    {
        $this->authorize('viewAny', Setting::class);

        return view('admin.settings.edit', [
            'settings' => $this->settings,
            'allowlist' => config('tsts.attachment_extension_allowlist'),
            'priorities' => Priority::active()->orderBy('level', 'desc')->get(),
            'mailConfigured' => MailConfig::isConfigured(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        $payload = $request->validated();

        $types = [
            'organization_name' => 'string',
            'system_name' => 'string',
            'system_short_name' => 'string',
            'support_email' => 'string',
            'support_phone' => 'string',
            'ticket_prefix' => 'string',
            'default_priority_id' => 'integer',
            'max_attachment_kb' => 'integer',
            'allowed_attachment_extensions' => 'json',
            'max_attachments_per_message' => 'integer',
            'reopen_window_days' => 'integer',
            'auto_close_days' => 'integer',
            'audit_retention_days' => 'integer',
            'notification_retention_days' => 'integer',
            'id_image_retention_days' => 'integer',
        ];

        foreach ($payload as $key => $value) {
            $this->settings->set($key, $value, $types[$key]);
        }

        $this->audit->log('settings_updated', null, 'Settings updated', null, array_keys($payload));

        return redirect()->route('admin.settings.index')->with('status', 'Settings saved.');
    }

    public function sendTestEmail(): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        if (! MailConfig::isConfigured()) {
            return back()->withErrors(['error' => 'Mail is not configured. Set MAIL_* values in your .env file first.']);
        }

        try {
            Mail::raw('This is a test email from the '.$this->settings->get('system_name').'. If you received this, your mail configuration is working.', function ($message) {
                $message->to(auth()->user()->email)->subject($this->settings->get('system_name', 'Tech Support Ticketing System').' — Test Email');
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['error' => 'The test email could not be sent: '.$e->getMessage()]);
        }

        $this->audit->log('test_email_sent', null, 'Test email sent to '.auth()->user()->email);

        return back()->with('status', 'Test email sent to '.auth()->user()->email.'.');
    }
}
