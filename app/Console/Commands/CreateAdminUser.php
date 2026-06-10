<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Company;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--name= : Full name of the admin user}
                            {--email= : Email / login}
                            {--password= : Password (prompted if omitted)}';

    protected $description = 'Create a super-admin user (backdoor — bypasses normal onboarding)';

    public function handle(): int
    {
        $name     = $this->option('name')     ?? $this->ask('Full name');
        $email    = $this->option('email')    ?? $this->ask('Email');
        $password = $this->option('password') ?? $this->secret('Password');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email [{$email}] already exists.");
            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        // Attach to every existing company
        $companies = Company::all();
        foreach ($companies as $company) {
            $user->companies()->syncWithoutDetaching([$company->id]);

            // Grant every system/admin role that exists
            $adminRoles = \App\Models\Role::where('is_system', true)->get();
            foreach ($adminRoles as $role) {
                \DB::table('user_roles')->insertOrIgnore([
                    'user_id'    => $user->id,
                    'role_id'    => $role->id,
                    'company_id' => $company->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->info("✓ Admin user created: {$email}");

        if ($companies->isEmpty()) {
            $this->warn('No companies exist yet. Visit /company/create to set one up first, then re-run this command to attach the admin role.');
        } else {
            $this->info("✓ Attached to {$companies->count()} company/companies with all system roles.");
        }

        return self::SUCCESS;
    }
}
