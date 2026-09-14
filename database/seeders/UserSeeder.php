<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SEED_USER_EMAIL', 'admin@example.com');
        $password = (string) env('SEED_USER_PASSWORD', 'password');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
            ],
        );

        $this->command?->info("Seed user: {$user->email} (".($user->wasRecentlyCreated ? 'created' : 'updated').')');
    }
}
