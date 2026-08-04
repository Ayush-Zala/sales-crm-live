<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog;
use App\Models\ZoomApi;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->user = Auth::check() ? Auth::user()->name : null;
    }


    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {

        // get zoom api key and secret from zoom_api table
        $zoom_api = ZoomApi::where('user_id', Auth::id())->select('account_id', 'client_key', 'client_secret')->first();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'zoomApiDetails' => $zoom_api,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        $properties = [
            'attributes' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->user . " has updated profile",
            'subject_type' => 'App\Models\User',
            'event' => 'updated',
            'subject_id' => $request->user()->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Profile updated successfully'], 201);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
