<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Models\Concerns\InteractsWithPasskeys;

class User extends Authenticatable implements HasPasskeys
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, InteractsWithPasskeys, Notifiable;

    public const RECOVERY_CODE_COUNT = 8;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // TOTP is on once the admin confirmed it with a code from their authenticator app
    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    /**
     * Check a code from the authenticator app. Each code is accepted only once, also within its 30 seconds.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!$this->hasTwoFactor() || !preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        // With an old time step (0 the first time) it returns the time step of the code, which is stored against replays
        $timestep = app(Google2FA::class)->verifyKeyNewer($this->two_factor_secret, $code, $this->two_factor_last_timestep ?? 0, 1);
        if (!is_int($timestep)) {
            return false;
        }

        $this->forceFill(['two_factor_last_timestep' => $timestep])->save();

        return true;
    }

    // A recovery code can be used once instead of a code from the app
    public function useRecoveryCode(string $code): bool
    {
        $code = strtolower(trim($code));
        $codes = $this->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $stored) {
            if (hash_equals($stored, $code)) {
                unset($codes[$index]);
                $this->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    public static function generateRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODE_COUNT))
            ->map(fn () => strtolower(Str::random(5) . '-' . Str::random(5)))
            ->all();
    }

    public function disableTwoFactor(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_timestep' => null,
        ])->save();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
