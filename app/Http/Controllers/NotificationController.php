<?php

namespace App\Http\Controllers;

use App\Models\DeviceTokens;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
        public function saveToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        // ربط التوكن بالمستخدم الحالي
        DeviceTokens::updateOrCreate(
            ['token' => $request->token], // الشرط لمنع التكرار
            ['user_id' => $request->user()->id] // البيانات المحدثة
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Device token saved successfully.'
        ]);
    }
}
