<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Session;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $active = User::where('email', $request->email)->value('is_active');

        if ($active > 0) {
            $request->authenticate();
            $request->session()->regenerate();

            // call the function tokenGenerate from ZoomController
            $zoomController = new \App\Http\Controllers\ZoomController();
            $zoomController->tokenGenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        } else {
            return back()->withErrors(['email' => 'Your account is not active. Please contact the administrator.']);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {

            $userid = Auth::id();

            DB::table('cache')->Where('key', 'user-is-online-' . $userid)->delete();

            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->intended(route('', absolute: false));

        } catch (\Exception $e) {
            \Log::error('Logout error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Logout failed.']);
        }
    }
}
