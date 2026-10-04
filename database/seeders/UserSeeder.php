<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->create([
            'email' => 'vahidkaargar@gmail.com',
            'name' => 'vahidkaargar',
            'password' => bcrypt('vahidkaargar'),

        ]);
        $user->email_verified_at = now();
        $user->save();
        $user->addRole('admin');

        $user->createWallet([
            'name' => 'USD Wallet',
            'slug' => 'usd',
            'currency' => 'USD',
            'description' => 'Primary USD wallet',
            'is_active' => true,
        ]);

        $user->deposit('usd', '1000.00');
        $user->withdraw('usd', '700');
        $user->grantCredit('usd', 2000.22);
        $user->revokeCredit('usd', 500.5);
        $user->lockFunds('usd', 200);
        $user->unlockFunds('usd', 100);
        $user->withdraw('usd', '99.11', autoApprove: false);
        $user->deposit('usd', '99.00', autoApprove: false);
        $user->deposit('usd', '44.00', autoApprove: false);

    }
}
