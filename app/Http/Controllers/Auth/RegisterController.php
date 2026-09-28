<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Department;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Notifications\NewRegistrationPending;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function showRegistrationForm(): View
    {
        $departments = Department::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('auth.register', compact('departments'));
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        // Rate limit registrations by IP *and* by email so an attacker can
        // neither carpet-bomb unique addresses from one host nor exhaust a
        // single address's quota from many hosts.
        $keys = [
            'registration:ip:'.$request->ip(),
            'registration:email:'.strtolower($request->string('email')),
        ];

        foreach ($keys as $key) {
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);

                return back()
                    ->withErrors([
                        'email' => 'Too many registration attempts. Please try again in '.ceil($seconds / 60).' minute(s).',
                    ])
                    ->onlyInput('email');
            }
        }

        foreach ($keys as $key) {
            RateLimiter::hit($key, 3600);
        }

        $user = DB::transaction(function () use ($request) {
            $idNumber = $request->validated('id_number');
            $existing = User::query()
                ->where('email', $request->validated('email'))
                ->first();

            $isResubmission = $existing !== null
                && $existing->isRequester()
                && $existing->isRejected();

            $imagePath = $this->storeIdImage($request->file('id_image'));

            if ($isResubmission) {
                $existing->update(
                    $request->userAttributes()
                    + [
                        'employee_id' => $idNumber,
                        'password' => $request->validated('password'),
                        'account_status' => AccountStatus::Pending,
                        'is_active' => false,
                        'must_change_password' => false,
                    ]
                );

                $user = $existing;

                $this->audit->log(
                    'registration_resubmitted',
                    $user,
                    'Registration resubmitted by '.$user->full_name.'.',
                    null,
                    ['id_type' => $request->validated('id_type'), 'id_number' => $idNumber],
                    $user->id
                );
            } else {
                $user = User::create(
                    $request->userAttributes()
                    + [
                        'employee_id' => $idNumber,
                        'role' => UserRole::Requester,
                        'account_status' => AccountStatus::Pending,
                        'is_active' => false,
                        'must_change_password' => false,
                    ]
                );

                $this->audit->log(
                    'registration_requested',
                    $user,
                    'Account registration requested by '.$user->full_name.'.',
                    null,
                    $user->first(['employee_id', 'email', 'department_id', 'position'])->toArray(),
                    $user->id
                );
            }

            VerificationRequest::create([
                'user_id' => $user->id,
                'id_type' => $request->validated('id_type'),
                'id_number' => $idNumber,
                'id_image_path' => $imagePath,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            return $user;
        });

        // Notify all active administrators that a registration is pending approval.
        $administrators = User::query()
            ->where('role', UserRole::Admin)
            ->where('is_active', true)
            ->get();

        Notification::send($administrators, new NewRegistrationPending($user));

        return redirect()
            ->route('login')
            ->with('status', 'Your account has been created and is awaiting administrator approval. You will be able to sign in once an administrator approves your account.');
    }

    /**
     * Store the uploaded ID document on the private disk under a random name.
     */
    protected function storeIdImage(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        return $file->storeAs(
            'verifications',
            Str::random(40).'.'.$extension,
            'private'
        );
    }
}
