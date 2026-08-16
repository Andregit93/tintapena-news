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
        $name = config('tintapena.admin.name');
        $email = config('tintapena.admin.email');
        $password = config('tintapena.admin.password');

        if (empty($email) || empty($password)) {
            if (isset($this->command)) {
                $this->command->warn('Admin user not created: Missing TINTAPENA_ADMIN_EMAIL or TINTAPENA_ADMIN_PASSWORD in environment.');
            }

            return;
        }

        // Do not use UserFactory, use firstOrCreate.
        // We do not overwrite password if the user exists.
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password, // Password casting handles hashing
            ]
        );

        if ($user->wasRecentlyCreated && isset($this->command)) {
            $this->command->info("Admin user created: {$email}");
        } elseif (isset($this->command)) {
            $this->command->info("Admin user already exists: {$email}");
        }
    }
}
