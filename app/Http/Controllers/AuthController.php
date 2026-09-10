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
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    public function show(): View
    {
        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:1024'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => trans_choice(
                    'Demasiados intentos. Probá nuevamente en :count segundo.|Demasiados intentos. Probá nuevamente en :count segundos.',
                    $seconds,
                    ['count' => $seconds],
                ),
            ]);
        }

        if (! Auth::attempt([
            'email' => Str::lower($credentials['email']),
            'password' => $credentials['password'],
            'is_admin' => true,
        ], $request->boolean('remember'))) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'No pudimos iniciar sesión con esos datos.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function throttleKey(Request $request): string
    {
        return 'admin-login:'.hash('sha256', Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
