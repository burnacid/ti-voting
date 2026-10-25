<?php

use App\Livewire\Admin\Login;
use App\Livewire\Admin\Security;
use App\Livewire\Admin\TwoFactorChallenge;
use App\Models\User;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

function adminWithTotp(): array
{
    $secret = app(Google2FA::class)->generateSecretKey(32);
    $user = User::factory()->create(['password' => 'correct horse battery']);
    $user->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => ['aaaaa-bbbbb', 'ccccc-ddddd'],
        'two_factor_confirmed_at' => now(),
    ])->save();

    return [$user, $secret];
}

function passwordLogin(User $user): void
{
    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct horse battery')
        ->call('login')
        ->assertRedirect(route('admin.two-factor'));
}

test('an admin can set up an authenticator app', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(Security::class)
        ->call('startSetup')
        ->assertSee('Scan the QR code');
    $secret = session('two_factor_setup_secret');

    $component->set('code', '000000')
        ->call('confirmSetup')
        ->assertHasErrors('code')
        ->set('code', app(Google2FA::class)->getCurrentOtp($secret))
        ->call('confirmSetup')
        ->assertHasNoErrors()
        ->assertSee('Save your recovery codes');

    $user->refresh();
    expect($user->hasTwoFactor())->toBeTrue()
        ->and($user->two_factor_secret)->toBe($secret)
        ->and($user->two_factor_recovery_codes)->toHaveCount(User::RECOVERY_CODE_COUNT)
        // Stored encrypted
        ->and($user->getRawOriginal('two_factor_secret'))->not->toContain($secret);
});

test('with an authenticator app the password alone does not log in', function () {
    [$user, $secret] = adminWithTotp();

    passwordLogin($user);
    $this->assertGuest();

    Livewire::test(TwoFactorChallenge::class)
        ->set('code', '000000')
        ->call('verify')
        ->assertHasErrors('code');
    $this->assertGuest();

    Livewire::test(TwoFactorChallenge::class)
        ->set('code', app(Google2FA::class)->getCurrentOtp($secret))
        ->call('verify')
        ->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('a code from the authenticator app works only once', function () {
    [$user, $secret] = adminWithTotp();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    expect($user->verifyTwoFactorCode($code))->toBeTrue()
        ->and($user->fresh()->verifyTwoFactorCode($code))->toBeFalse();
});

test('a recovery code logs in once', function () {
    [$user] = adminWithTotp();

    passwordLogin($user);
    Livewire::test(TwoFactorChallenge::class)
        ->call('toggleRecoveryCode')
        ->set('code', 'AAAAA-BBBBB')
        ->call('verify')
        ->assertRedirect(route('admin.dashboard'));

    expect($user->fresh()->two_factor_recovery_codes)->toBe(['ccccc-ddddd']);
});

test('the code step needs a recent password login and is throttled', function () {
    [$user] = adminWithTotp();

    // Without the password step
    Livewire::test(TwoFactorChallenge::class)->assertRedirect(route('admin.login'));

    passwordLogin($user);
    $component = Livewire::test(TwoFactorChallenge::class);
    foreach (range(1, 5) as $attempt) {
        $component->set('code', '000000')->call('verify');
    }
    $component->set('code', '000000')->call('verify')->assertSee('Too many attempts');

    // The password login expires after 5 minutes
    $this->travel(6)->minutes();
    Livewire::test(TwoFactorChallenge::class)->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

test('turning off the authenticator app needs the password', function () {
    [$user] = adminWithTotp();
    $this->actingAs($user);

    Livewire::test(Security::class)
        ->set('password', 'wrong')
        ->call('disable')
        ->assertHasErrors('password');
    expect($user->fresh()->hasTwoFactor())->toBeTrue();

    Livewire::test(Security::class)
        ->set('password', 'correct horse battery')
        ->call('regenerateRecoveryCodes')
        ->assertSee('Save your recovery codes')
        ->set('password', 'correct horse battery')
        ->call('disable');
    expect($user->fresh()->hasTwoFactor())->toBeFalse();
});

test('the security page and the passkey login are shown', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.security'))
        ->assertOk()
        ->assertSee('Passkeys')
        ->assertSee('Add passkey')
        ->assertSee('Authenticator app');

    auth()->logout();
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('Log in with a passkey')
        ->assertSee(route('passkeys.login'));

    $this->getJson(route('passkeys.authentication_options'))->assertOk()->assertJsonStructure(['challenge']);
});

test('an admin who lost their authenticator app can be reset from the command line', function () {
    [$user] = adminWithTotp();

    $this->artisan('admin:reset-2fa', ['email' => $user->email, '--force' => true])->assertSuccessful();

    expect($user->fresh()->hasTwoFactor())->toBeFalse();
    $this->artisan('admin:reset-2fa', ['email' => 'nobody@example.com', '--force' => true])->assertFailed();
});
