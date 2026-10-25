<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in ' . RateLimiter::availableIn($throttleKey) . ' seconds.',
            ]);
        }

        // Only checks the password: with TOTP on, the admin is logged in after the code
        if (!Auth::validate(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::hit($throttleKey);
            Log::warning('Failed admin login', ['email' => $this->email, 'ip' => request()->ip()]);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $user = Auth::getLastAttempted();

        if ($user->hasTwoFactor()) {
            session()->regenerate();
            session(['two_factor_login' => ['id' => $user->id, 'remember' => (bool) $this->remember, 'expires' => now()->addMinutes(5)->timestamp]]);

            return $this->redirectRoute('admin.two-factor');
        }

        Auth::login($user, (bool) $this->remember);
        session()->regenerate();
        Log::info('Admin logged in', ['email' => $user->email, 'ip' => request()->ip(), 'method' => 'password']);

        return $this->redirectIntended(route('admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.admin.login');
    }
}
