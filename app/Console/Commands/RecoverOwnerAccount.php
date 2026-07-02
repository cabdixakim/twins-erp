<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class RecoverOwnerAccount extends Command
{
    protected $signature = 'admin:recover-owner
                            {--email= : Email of the existing owner account to recover}
                            {--password= : New password (prompted if omitted)}';

    protected $description = 'Maintainer recovery tool — resets the password and reactivates an EXISTING owner account. Cannot create new accounts or new roles.';

    public function handle(): int
    {
        $email = $this->option('email') ?? $this->ask('Email of the owner account to recover');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("No user found with email [{$email}].");
            return self::FAILURE;
        }

        if ($user->role?->slug !== 'owner') {
            $this->error("User [{$email}] is not an owner account (role: {$user->role?->slug}). This tool only recovers owner accounts.");
            return self::FAILURE;
        }

        $this->warn("You are about to reset the password for: {$user->name} <{$user->email}>");
        if (!$this->confirm('Are you sure you want to continue?', true)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $password = $this->option('password') ?? $this->secret('New password');

        $user->password       = Hash::make($password);
        $user->status         = 'active';
        $user->recovery_token = null;
        $user->save();

        $this->info("✓ Password reset and account reactivated for: {$email}");
        $this->info('No new accounts or roles were created — only this existing owner account was touched.');

        return self::SUCCESS;
    }
}
