<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendMessage(Request $request, $conversationId)
    {
        // 1. استلام نص الرسالة من تطبيق الموبايل (Flutter)
        $text = $request->input('body');

        // جلب المستخدم الحالي (طبيب أو مريض)
        $user = $request->user();

        // 2. للتبسيط الآن (وبما أننا لم ننشئ جداول قاعدة البيانات بعد)،
        // سنقوم بتجهيز رسالة "وهمية" مطابقة لشكل الرسائل الذي ينتظره كود Flutter
        $message = [
            'id' => rand(1000, 9999),
            'conversation_id' => (int) $conversationId,
            'authorId' => $user ? (string) $user->id : "1", // معرف افتراضي للتبسيط
            'text' => $text,
            'createdAt' => now()->timestamp * 1000,
        ];

        // 3. إطلاق الحدث لإرسال الرسالة إلى الطرف الآخر عبر Pusher
        // دالة toOthers() تضمن عدم ارتداد الرسالة لنفس الشخص الذي أرسلها
        broadcast(new MessageSent($message, $conversationId))->toOthers();

        // 4. الرد على التطبيق بنجاح العملية ليقوم بعرض الرسالة على الشاشة
        return response()->json([
            'message' => $message
        ]);
    }
}
