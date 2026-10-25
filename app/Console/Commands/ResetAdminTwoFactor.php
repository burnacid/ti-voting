<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\confirm;

/**
 * For an admin who lost their authenticator app and recovery codes: they log in with only their password again.
 */
class ResetAdminTwoFactor extends Command
{
    protected $signature = 'admin:reset-2fa {email : Email address of the admin} {--passkeys : Also delete the admin\'s passkeys} {--force : Do not ask for confirmation}';

    protected $description = 'Turn off the authenticator app of an admin, and optionally delete their passkeys';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('No admin with that email address.');
            return self::FAILURE;
        }

        $what = $this->option('passkeys') ? 'the authenticator app and passkeys' : 'the authenticator app';
        if (!$this->option('force') && !confirm("Turn off {$what} for {$user->email}?")) {
            return self::FAILURE;
        }

        $user->disableTwoFactor();
        $passkeys = $this->option('passkeys') ? $user->passkeys()->delete() : 0;

        Log::warning('Admin two-factor reset from the command line', ['admin' => $user->email, 'passkeys_deleted' => $passkeys]);
        $this->info("Turned off {$what} for {$user->email}." . ($this->option('passkeys') ? " Passkeys deleted: {$passkeys}." : ''));

        return self::SUCCESS;
    }
}
