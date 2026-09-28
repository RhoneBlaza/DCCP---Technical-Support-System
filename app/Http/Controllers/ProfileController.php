<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function show(): View
    {
        return view('profile.show', ['user' => auth()->user()->load('department')]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except(['photo']);

        $old = $user->only(array_keys($data));

        if ($request->hasFile('photo')) {
            // Profile photos are not publicly served anywhere, so keep them on
            // the private disk rather than the web-accessible public disk.
            $path = $request->file('photo')->storeAs(
                'profile-photos',
                'user-'.$user->id.'-'.Str::random(8).'.'.$request->file('photo')->extension(),
                'private'
            );

            if ($user->profile_photo_path !== null) {
                Storage::disk('private')->delete($user->profile_photo_path);
            }

            $data['profile_photo_path'] = $path;
        }

        $user->fill($data)->save();

        $this->audit->log('profile_updated', $user, 'User updated their own profile', $old, $data);

        return back()->with('status', 'Profile updated successfully.');
    }
}
