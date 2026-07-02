<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    public function storeDeviceToken(Request $request)
    {
        // 1. التحقق من البيانات القادمة من الفرونت إند
        $request->validate([
            'token' => 'required|string',
        ]);

        // 2. الحصول على المستخدم الصاعد (المسجل دخوله حالياً)
        $user = Auth::user();

        // 3. تحديث التوكن إذا كان موجوداً لنفس المستخدم، أو إنشاؤه إذا كان جديداً
        // استخدمنا updateOrCreate لتجنب تكرار نفس التوكن لنفس المستخدم
        $user->deviceTokens()->updateOrCreate(
            ['token' => $request->token], // شرط البحث
            ['token' => $request->token]  // البيانات المراد حفظها
        );

        // 4. إرجاع استجابة نجاح للفرونت إند
        return response()->json([
            'success' => true,
            'message' => 'Device token saved successfully.',
        ], 200);
    }
    /**
     * حذف توكن الجهاز (عند تسجيل الخروج مثلاً لمنع وصول الإشعارات لهذا الجهاز لاحقاً)
     */
    public function destroyDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();

        // حذف التوكن الخاص بهذا المستخدم فقط لحماية البيانات
        $user->deviceTokens()->where('token', $request->token)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device token removed successfully.',
        ], 200);
    }
}

