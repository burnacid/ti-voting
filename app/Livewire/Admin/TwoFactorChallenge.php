<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/**
 * Second step of the admin login when TOTP is on: a code from the authenticator app, or a recovery code.
 * The password was checked on the login page, which keeps the admin in the session for 5 minutes.
 */
class TwoFactorChallenge extends Component
{
    public $code = '';
    public $useRecoveryCode = false;

    public function mount()
    {
        if (!$this->pendingUser()) {
            return $this->redirectRoute('admin.login');
        }
    }

    public function toggleRecoveryCode(): void
    {
        $this->useRecoveryCode = !$this->useRecoveryCode;
        $this->reset('code');
        $this->resetErrorBag();
    }

    public function verify()
    {
        $user = $this->pendingUser();
        if (!$user) {
            session()->flash('status', 'The login expired. Log in again.');
            return $this->redirectRoute('admin.login');
        }

        $this->validate(['code' => 'required|string|max:32']);

        $throttleKey = 'two-factor|' . $user->id . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('code', 'Too many attempts. Try again in ' . RateLimiter::availableIn($throttleKey) . ' seconds.');
            return;
        }

        $valid = $this->useRecoveryCode ? $user->useRecoveryCode($this->code) : $user->verifyTwoFactorCode($this->code);

        if (!$valid) {
            RateLimiter::hit($throttleKey, 300);
            Log::warning('Failed admin two-factor code', ['email' => $user->email, 'ip' => request()->ip(), 'recovery_code' => $this->useRecoveryCode]);
            $this->addError('code', $this->useRecoveryCode ? 'That recovery code is not valid.' : 'That code is not valid.');
            return;
        }

        RateLimiter::clear($throttleKey);
        $remember = session('two_factor_login.remember', false);
        session()->forget('two_factor_login');

        Auth::login($user, $remember);
        session()->regenerate();
        Log::info('Admin logged in', [
            'email' => $user->email,
            'ip' => request()->ip(),
            'method' => $this->useRecoveryCode ? 'password and recovery code' : 'password and TOTP',
            'recovery_codes_left' => $this->useRecoveryCode ? count($user->two_factor_recovery_codes ?? []) : null,
        ]);

        return $this->redirectIntended(route('admin.dashboard'));
    }

    private function pendingUser(): ?User
    {
        $pending = session('two_factor_login');
        if (!$pending || ($pending['expires'] ?? 0) < now()->timestamp) {
            return null;
        }

        $user = User::find($pending['id']);

        return $user?->hasTwoFactor() ? $user : null;
    }

    public function render()
    {
        return view('livewire.admin.two-factor-challenge');
    }
}
