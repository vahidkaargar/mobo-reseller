<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:grant-admin-role {email : Email of the user to promote} {--revoke : Remove the admin role instead}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant or revoke the admin role for a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('No user found with that email.');

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            $user->removeRole('admin');
            $this->info("Admin role revoked from {$user->email}.");

            return self::SUCCESS;
        }

        if (! $user->hasRole('admin')) {
            $user->addRole('admin');
        }
        $this->info("Admin role granted to {$user->email}.");

        return self::SUCCESS;
    }
}
