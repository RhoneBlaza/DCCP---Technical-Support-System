<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Notifications\VerificationApprovedNotification;
use App\Notifications\VerificationRejectedNotification;
use App\Notifications\VerificationResubmissionRequestedNotification;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationRequestsController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', VerificationRequest::class);

        $requests = VerificationRequest::query()
            ->with(['user.department', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim($request->string('search'));

                $q->where(function ($query) use ($term) {
                    $query
                        ->whereLike('id_number', $term)
                        ->orWhereHas('user', fn ($u) => $u
                            ->whereLike('first_name', $term)
                            ->orWhereLike('last_name', $term)
                            ->orWhereLike('email', $term));
                });
            })
            ->latest('submitted_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.verifications.index', [
            'requests' => $requests,
            'statuses' => ['pending', 'approved', 'rejected', 'resubmit_requested'],
        ]);
    }

    public function show(VerificationRequest $verificationRequest): View
    {
        $this->authorize('view', $verificationRequest);

        $verificationRequest->load(['user.department', 'reviewer', 'user.verificationRequests']);

        $duplicateId = User::query()
            ->where('employee_id', $verificationRequest->id_number)
            ->whereKeyNot($verificationRequest->user_id)
            ->exists();

        if (! $duplicateId) {
            $duplicateId = VerificationRequest::query()
                ->where('id_number', $verificationRequest->id_number)
                ->whereKeyNot($verificationRequest->id)
                ->exists();
        }

        return view('admin.verifications.show', [
            'request' => $verificationRequest,
            'duplicateId' => $duplicateId,
            'history' => $verificationRequest->user->verificationRequests,
        ]);
    }

    /**
     * Serve the uploaded ID document. Admin only, never cached, always logged.
     */
    public function image(VerificationRequest $verificationRequest): StreamedResponse
    {
        $this->authorize('viewIdImage', $verificationRequest);

        $path = $verificationRequest->id_image_path;

        if (! $path || ! Storage::disk('private')->exists($path)) {
            abort(404);
        }

        $this->audit->log(
            'id_image_viewed',
            $verificationRequest,
            'ID document ('.$verificationRequest->getIdTypeLabelAttribute().') viewed for '.$verificationRequest->user->full_name
        );

        return Storage::disk('private')->response(
            $path,
            'id-document-'.$verificationRequest->id.'.'.pathinfo($path, PATHINFO_EXTENSION),
            [
                'Cache-Control' => 'no-store, private, max-age=0',
                'Pragma' => 'no-cache',
            ]
        );
    }

    public function approve(VerificationRequest $verificationRequest): RedirectResponse
    {
        $this->authorize('approve', $verificationRequest);

        $user = $verificationRequest->user;

        $verificationRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'decision_note' => null,
        ]);

        $user->update([
            'account_status' => AccountStatus::Approved,
            'is_active' => true,
        ]);

        $this->audit->log('registration_approved', $user, 'Registration approved for '.$user->full_name.'. '.$user->full_name.' can now sign in.');

        $user->notify(new VerificationApprovedNotification($verificationRequest));

        return redirect()->route('admin.verifications.show', $verificationRequest)
            ->with('status', 'Registration approved. '.$user->full_name.' can now sign in.');
    }

    public function reject(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        $this->authorize('reject', $verificationRequest);

        $validated = $request->validate([
            'decision_note' => ['required', 'string', 'max:1000'],
        ]);

        $user = $verificationRequest->user;

        $verificationRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'decision_note' => $validated['decision_note'],
        ]);

        $user->update([
            'account_status' => AccountStatus::Rejected,
            'is_active' => false,
        ]);

        $this->audit->log('registration_rejected', $user, 'Registration rejected for '.$user->full_name.'. They cannot sign in.', null, ['reason' => $validated['decision_note']]);

        $user->notify(new VerificationRejectedNotification($verificationRequest));

        return redirect()->route('admin.verifications.show', $verificationRequest)
            ->with('status', 'Registration rejected. '.$user->full_name.' has been notified.');
    }

    public function requestResubmission(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        $this->authorize('requestResubmission', $verificationRequest);

        $validated = $request->validate([
            'decision_note' => ['required', 'string', 'max:1000'],
        ]);

        $user = $verificationRequest->user;

        $verificationRequest->update([
            'status' => 'resubmit_requested',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'decision_note' => $validated['decision_note'],
        ]);

        $this->audit->log('verification_resubmission_requested', $verificationRequest, 'Resubmission requested for '.$user->full_name.'.', null, ['reason' => $validated['decision_note']]);

        $user->notify(new VerificationResubmissionRequestedNotification($verificationRequest));

        return redirect()->route('admin.verifications.show', $verificationRequest)
            ->with('status', 'Resubmission requested. '.$user->full_name.' has been notified.');
    }
}
