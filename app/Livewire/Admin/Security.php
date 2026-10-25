<?php

namespace App\Livewire\Admin;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use PragmaRX\Google2FA\Google2FA;

/**
 * The admin's own sign-in security: TOTP with recovery codes, and passkeys (managed by spatie/laravel-passkeys).
 */
class Security extends Component
{
    // True while setting up TOTP. The new secret is kept in the session, not in the browser
    public bool $settingUp = false;
    public $code = '';
    // Shown once, right after they are made
    public array $newRecoveryCodes = [];
    // Turning TOTP off and new recovery codes need the password
    public $password = '';

    public function startSetup(): void
    {
        if (Auth::user()->hasTwoFactor()) {
            return;
        }

        session(['two_factor_setup_secret' => app(Google2FA::class)->generateSecretKey(32)]);
        $this->settingUp = true;
        $this->reset('code');
    }

    public function cancelSetup(): void
    {
        session()->forget('two_factor_setup_secret');
        $this->settingUp = false;
        $this->reset('code');
    }

    public function confirmSetup(): void
    {
        $secret = session('two_factor_setup_secret');
        $this->validate(['code' => 'required|string|max:10']);

        $timestep = $secret ? app(Google2FA::class)->verifyKeyNewer($secret, preg_replace('/\s+/', '', $this->code), 0, 1) : false;
        if (!is_int($timestep)) {
            $this->addError('code', 'That code is not valid. Check the time on your phone and try again.');
            return;
        }

        $codes = \App\Models\User::generateRecoveryCodes();
        Auth::user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
            'two_factor_last_timestep' => $timestep,
        ])->save();

        session()->forget('two_factor_setup_secret');
        Log::info('Admin turned on two-factor authentication', ['admin' => Auth::user()->email]);

        $this->settingUp = false;
        $this->newRecoveryCodes = $codes;
        $this->reset('code');
    }

    public function regenerateRecoveryCodes(): void
    {
        if (!$this->checkPassword() || !Auth::user()->hasTwoFactor()) {
            return;
        }

        $codes = \App\Models\User::generateRecoveryCodes();
        Auth::user()->forceFill(['two_factor_recovery_codes' => $codes])->save();
        Log::info('Admin made new recovery codes', ['admin' => Auth::user()->email]);

        $this->newRecoveryCodes = $codes;
        $this->reset('password');
    }

    public function disable(): void
    {
        if (!$this->checkPassword()) {
            return;
        }

        Auth::user()->disableTwoFactor();
        Log::warning('Admin turned off two-factor authentication', ['admin' => Auth::user()->email]);

        $this->newRecoveryCodes = [];
        $this->reset('password');
    }

    public function hideRecoveryCodes(): void
    {
        $this->newRecoveryCodes = [];
    }

    private function checkPassword(): bool
    {
        $this->validate(['password' => 'required|string']);

        if (!Hash::check($this->password, Auth::user()->password)) {
            $this->addError('password', 'The password is not correct.');
            return false;
        }

        return true;
    }

    public function render()
    {
        $user = Auth::user();
        $secret = $this->settingUp ? session('two_factor_setup_secret') : null;

        return view('livewire.admin.security', [
            'user' => $user,
            'secret' => $secret,
            'qrCode' => $secret ? $this->qrCode($user->email, $secret) : null,
            'recoveryCodesLeft' => count($user->two_factor_recovery_codes ?? []),
        ]);
    }

    // QR code made here, so the secret is never sent to a QR service
    private function qrCode(string $email, string $secret): string
    {
        $url = app(Google2FA::class)->getQRCodeUrl(config('app.name') . ' Admin', $email, $secret);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd())))->writeString($url);

        return preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
    }
}
