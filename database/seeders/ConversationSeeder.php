<?php

namespace Database\Seeders;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::query()->where('email', 'user1@gmail.com')->first();
        $user2 = User::query()->where('email', 'user2@gmail.com')->first();

        if (! $user1 || ! $user2) {
            return;
        }

        $closedAt = Carbon::now()->subDays(2);

        Conversation::firstOrCreate(
            ['user_id' => $user1->id],
            [
                'status' => ConversationStatus::CLOSED,
                'last_message_at' => $closedAt,
                'last_closed_at' => $closedAt,
            ]
        );

        Conversation::firstOrCreate(
            ['user_id' => $user2->id],
            [
                'status' => ConversationStatus::CLOSED,
                'last_message_at' => $closedAt,
                'last_closed_at' => $closedAt,
            ]
        );
    }
}
