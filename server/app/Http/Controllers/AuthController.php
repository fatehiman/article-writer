<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('themes.'.ui_theme().'.login');
    }

    public function login(Request $request): RedirectResponse
    {
        // Honeypot: real users never fill this hidden field.
        if ($request->filled('website')) {
            abort(422);
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $key = 'login:'.Str::lower($data['username']).'|'.$request->ip();
        $ipKey = 'login-ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 20)) {
            $seconds = max(RateLimiter::availableIn($key), RateLimiter::availableIn($ipKey));
            throw ValidationException::withMessages(['username' => __('ui.too_many_attempts', ['seconds' => $seconds])]);
        }

        $credentials = [
            'username' => Str::lower($data['username']),
            'password' => $data['password'],
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 15 * 60);
            RateLimiter::hit($ipKey, 60 * 60);
            throw ValidationException::withMessages(['username' => __('ui.wrong_login')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /** Switch UI language. Saved on the user when signed in. */
    public function locale(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('languages.ui')), 404);
        $request->session()->put('locale', $locale);
        $request->user()?->forceFill(['locale' => $locale])->save();

        return redirect()->back(fallback: route('login'));
    }
}
