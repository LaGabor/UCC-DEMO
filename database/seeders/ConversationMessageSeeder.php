<?php

namespace Database\Seeders;

use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\Language;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationMessageSeeder extends Seeder
{
    public function run(): void
    {
        $agent = User::query()->where('email', 'agent@gmail.com')->first();
        $user1 = User::query()->where('email', 'user1@gmail.com')->first();
        $user2 = User::query()->where('email', 'user2@gmail.com')->first();

        if (! $agent || ! $user1 || ! $user2) {
            return;
        }

        $conversation1 = Conversation::query()->where('user_id', $user1->id)->first();
        $conversation2 = Conversation::query()->where('user_id', $user2->id)->first();

        if (! $conversation1 || ! $conversation2) {
            return;
        }

        if ($conversation1->messages()->exists() || $conversation2->messages()->exists()) {
            return;
        }

        $huMessages = [
            ['sender' => ConversationMessageSenderType::USER, 'type' => ConversationMessageType::QUESTION, 'text' => 'Segítségre lenne szükségem a bejelentkezéssel.'],
            ['sender' => ConversationMessageSenderType::AGENT, 'type' => ConversationMessageType::AGENT_ANSWER, 'text' => 'Helló! Miben segíthetek? Elmondanád részletesebben a problémát?'],
            ['sender' => ConversationMessageSenderType::USER, 'type' => ConversationMessageType::QUESTION, 'text' => 'Elfelejtettem a jelszavam, hogyan állíthatom vissza?'],
            ['sender' => ConversationMessageSenderType::AGENT, 'type' => ConversationMessageType::AGENT_ANSWER, 'text' => 'A bejelentkezési oldalon kattints az „Elfelejtett jelszó” linkre, add meg az e-mail címed, és küldünk egy visszaállító linket.'],
            ['sender' => ConversationMessageSenderType::USER, 'type' => ConversationMessageType::QUESTION, 'text' => 'Köszönöm, sikerült!'],
            ['sender' => ConversationMessageSenderType::AGENT, 'type' => ConversationMessageType::AGENT_ANSWER, 'text' => 'Örülök, hogy segíthettem. Ha még bármi kérdésed van, írj nyugodtan!'],
        ];

        $enMessages = [
            ['sender' => ConversationMessageSenderType::USER, 'type' => ConversationMessageType::QUESTION, 'text' => 'I need help with logging in.'],
            ['sender' => ConversationMessageSenderType::AGENT, 'type' => ConversationMessageType::AGENT_ANSWER, 'text' => 'Hello! How can I help? Could you describe the issue in more detail?'],
            ['sender' => ConversationMessageSenderType::USER, 'type' => ConversationMessageType::QUESTION, 'text' => 'I forgot my password. How can I reset it?'],
            ['sender' => ConversationMessageSenderType::AGENT, 'type' => ConversationMessageType::AGENT_ANSWER, 'text' => 'On the login page click the "Forgot password" link, enter your email, and we will send you a reset link.'],
            ['sender' => ConversationMessageSenderType::USER, 'type' => ConversationMessageType::QUESTION, 'text' => 'Thanks, it worked!'],
            ['sender' => ConversationMessageSenderType::AGENT, 'type' => ConversationMessageType::AGENT_ANSWER, 'text' => 'Glad I could help. If you have any other questions, feel free to ask!'],
        ];

        $last1 = null;
        foreach ($huMessages as $row) {
            $last1 = ConversationMessage::create([
                'conversation_id' => $conversation1->id,
                'sender_type' => $row['sender'],
                'message_type' => $row['type'],
                'sender_user_id' => $row['sender'] === ConversationMessageSenderType::USER ? $user1->id : $agent->id,
                'language' => Language::HU,
                'message_text' => $row['text'],
            ]);
        }
        if ($last1) {
            $conversation1->update(['last_message_at' => $last1->created_at]);
        }

        $last2 = null;
        foreach ($enMessages as $row) {
            $last2 = ConversationMessage::create([
                'conversation_id' => $conversation2->id,
                'sender_type' => $row['sender'],
                'message_type' => $row['type'],
                'sender_user_id' => $row['sender'] === ConversationMessageSenderType::USER ? $user2->id : $agent->id,
                'language' => Language::EN,
                'message_text' => $row['text'],
            ]);
        }
        if ($last2) {
            $conversation2->update(['last_message_at' => $last2->created_at]);
        }
    }
}
