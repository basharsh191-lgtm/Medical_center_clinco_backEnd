<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationsController extends Controller
{
    /**
     * 1. جلب كافة المحادثات الخاصة بالمستخدم الحالي (صفحة المحادثات)
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::whereHas('users', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        })
        ->with([
            // جلب بياتات الطرف الآخر فقط في المحادثة
            'users' => function ($q) use ($userId) {
                $q->where('users.id', '!=', $userId)->select('users.id', 'users.name');
            },
            // جلب آخر رسالة لعرضها في قائمة المحادثات
            'latestMessage'
        ])
        ->orderByDesc('updated_at')
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $conversations
        ]);
    }

    /**
     * 2. فتح أو إنشاء محادثة بين الطرف الحالي ومستخدم آخر (مثلاً محمد)
     */
public function store(Request $request)
{
    $request->validate([
        'receiver_id' => 'required|integer|exists:users,id',
    ]);

    $authUserId = $request->user()->id; // رقم بشار
    $receiverId = (int) $request->input('receiver_id'); // رقم محمد

    if ($authUserId === $receiverId) {
        return response()->json([
            'status'  => 'error',
            'message' => 'لا يمكنك إنشاء محادثة مع نفسك'
        ], 422);
    }

    // البحث عن محادثة ثنائية مسبقة تجمع الرقمين معاً
    $conversation = Conversation::where('type', 'direct')
        ->whereHas('users', function ($q) use ($authUserId) {
            $q->where('users.id', $authUserId);
        })
        ->whereHas('users', function ($q) use ($receiverId) {
            $q->where('users.id', $receiverId);
        })
        ->first();

    // إذا لم تكن موجودة، يتم إنشاؤها وربط الطرفين
    if (!$conversation) {
        $conversation = Conversation::create([
            'type' => 'direct',
        ]);

        // هامة جداً: ربط الطرفين (بشار ومحمد) بالمحادثة في الجدول الوسيط
        $conversation->users()->syncWithoutDetaching([$authUserId, $receiverId]);
    }

    return response()->json([
        'status'       => 'success',
        'conversation' => $conversation->load('users')
    ]);
}

    /**
     * 3. جلب رسائل محادثة معينة عند الضغط عليها
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        // التأكد من أن المستخدم ينتمي لهذه المحادثة
        $conversation = $user->conversations()
            ->with(['messages.user:id,name'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'messages' => $conversation->messages
        ]);
    }
}
