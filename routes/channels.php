<?php

use App\Enums\UserRole;
use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {
    $conversation = Conversation::query()->find($conversationId);
    if (! $conversation) {
        return false;
    }

    if ((int) $conversation->user_id === (int) $user->id) {
        return true;
    }

    return in_array($user->role, [UserRole::ADMIN, UserRole::HELPDESK_AGENT], true);
});

Broadcast::channel('agent-monitor', function ($user) {
    return in_array($user->role, [UserRole::ADMIN, UserRole::HELPDESK_AGENT], true);
});
