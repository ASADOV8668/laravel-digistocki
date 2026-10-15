<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\User;

class ChatConversationPolicy
{
    public function view(User $user, ChatConversation $conversation): bool
    {
        return $conversation->belongsToUser($user);
    }

    public function send(User $user, ChatConversation $conversation): bool
    {
        return $conversation->belongsToUser($user);
    }
}
