<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
public function sendMessage(Request $request, $conversationId)
{
    // 1. التحقق من المدخلات
    $request->validate([
        'body' => 'required|string',
    ]);

    $user = $request->user();

    // 2. التحقق من الصلاحيات: هل المستخدم الحالي عضو فعلي في هذه المحادثة؟
    $conversation = $user->conversations()->findOrFail($conversationId);

    // 3. حفظ الرسالة الحقيقية في قاعدة البيانات
    $message = $conversation->messages()->create([
        'user_id' => $user->id,
        'body'    => $request->input('body'),
    ]);

    // 4. تحديث timestamp المحادثة لتظهر في أعلى قائمة المحادثات (Updated At)
    $conversation->touch();

    // 5. تجهيز هيكلية البيانات المطلوبة لتطبيق الفلاتر
    $formattedMessage = [
        'id'              => $message->id,
        'conversation_id' => $message->conversation_id,
        'authorId'        => (string) $message->user_id,
        'text'            => $message->body,
        'createdAt'       => $message->created_at->timestamp * 1000,
    ];

    // 6. إطلاق الحدث عبر القناة الخاصة بـ Pusher
    broadcast(new MessageSent($formattedMessage, $conversationId))->toOthers();

    // 7. إرجاع الرد للتطبيق
    return response()->json([
        'status'  => 'success',
        'message' => $formattedMessage,
    ]);
}
}
