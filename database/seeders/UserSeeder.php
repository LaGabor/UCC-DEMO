<?php

namespace Database\Seeders;

use App\Enums\Language;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_SEED_PASSWORD', 'Password1!');

        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Test Admin',
                'password' => $password,
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'preferred_locale' => Language::EN,
            ]
        );

        User::updateOrCreate(
            ['email' => 'user1@gmail.com'],
            [
                'name' => 'Test User One',
                'password' => $password,
                'role' => UserRole::USER,
                'status' => UserStatus::ACTIVE,
                'preferred_locale' => Language::HU,
            ]
        );

        User::updateOrCreate(
            ['email' => 'user2@gmail.com'],
            [
                'name' => 'Test User Two',
                'password' => $password,
                'role' => UserRole::USER,
                'status' => UserStatus::ACTIVE,
                'preferred_locale' => Language::EN,
            ]
        );

        User::updateOrCreate(
            ['email' => 'agent@gmail.com'],
            [
                'name' => 'Test Agent',
                'password' => $password,
                'role' => UserRole::HELPDESK_AGENT,
                'status' => UserStatus::ACTIVE,
                'preferred_locale' => Language::HU,
            ]
        );
    }
}
