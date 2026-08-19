<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// القناة الافتراضية الخاصة بالمستخدمين في لارافل
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
return (int) $user->id === (int) $id;
});

// القناة الخاصة التي سيستمع لها تطبيق Flutter (تطبيق كلينكو)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
return true;
});
Broadcast::channel('chat.{conversationId}', function (User $user, $conversationId) {
    // يرجّع true فقط إذا كان المستخدم عضواً في جدول conversation_user لهذه المحادثة
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});
