<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'name' => 'Gabor',
                'email' => 'lagaborka@gmail.com',
                'password' => env('ADMIN_SEED_PASSWORD'),
                'role' => 'admin',
                'status' => 'active',
                'preferred_locale' => 'en',
            ]
        );
    }
}
