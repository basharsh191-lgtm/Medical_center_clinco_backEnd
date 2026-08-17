<?php

use Illuminate\Support\Facades\Broadcast;

// القناة الافتراضية الخاصة بالمستخدمين في لارافل
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
return (int) $user->id === (int) $id;
});

// القناة الخاصة التي سيستمع لها تطبيق Flutter (تطبيق كلينكو)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
return true;
});
