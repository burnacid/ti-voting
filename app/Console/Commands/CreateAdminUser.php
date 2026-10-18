<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {email? : Email address to log in with} {--name= : Display name}';

    protected $description = 'Create an admin account, or set a new password for an existing one';

    public function handle(): int
    {
        $email = $this->argument('email') ?? text('Email', required: true);

        if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
            $this->error('That is not a valid email address.');
            return self::FAILURE;
        }

        $password = password('Password (at least 12 characters)', required: true, validate: fn ($value) => strlen($value) < 12
            ? 'The password must be at least 12 characters.'
            : null);

        $user = User::firstOrNew(['email' => $email]);
        $exists = $user->exists;

        $user->name = $this->option('name') ?? $user->name ?? strstr($email, '@', true);
        $user->password = $password;
        $user->save();

        $this->info($exists ? "Password updated for {$email}." : "Admin {$email} created. Log in at " . route('admin.login'));

        return self::SUCCESS;
    }
}
