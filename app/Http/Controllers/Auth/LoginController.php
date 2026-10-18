<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\HumanCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Show the application login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'human' => ['required', HumanCheck::rule()],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'human.required' => 'Centang verifikasi "saya bukan robot" terlebih dahulu.',
        ]);

        // Hanya email & password yang dipakai untuk autentikasi (bukan field verifikasi)
        $credentials = $request->only('email', 'password');

        $remember = $request->boolean('remember');

        // Maksimal 5 percobaan gagal per email+IP, lalu dikunci 1 menit
        $throttleKey = Str::lower($credentials['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . $seconds . ' detik.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);

            if (Auth::user()->isSuspended()) {
                $reason = Auth::user()->suspend_reason;
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda sedang disuspend.' . ($reason ? ' Alasan: ' . $reason : ''),
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route(Auth::user()->homeRoute()));
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
