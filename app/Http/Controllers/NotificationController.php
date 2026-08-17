<?php

namespace App\Http\Controllers;

use App\Models\DeviceTokens;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function deleteToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        DeviceTokens::where('token', $request->token)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Device token deleted successfully.'
        ]);
    }
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }
    // وضع علامة مقروء لكل الإشعارات
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }
    public function getNotifications(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id);

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->has(['from', 'to'])) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // تعديل العناصر لإضافة last_name ديناميكياً
        $notifications->through(function ($notification) {
            $data = $notification->data;

            // إذا كان المريض مرتبئ بصلة مع نموذج الإشعار أو من جدول المستخدمين
        $patient = User::find($data['patient_id'] ?? null);
        $data['last_name'] = $patient ? $patient->last_name : null;

            $notification->data = $data;
            return $notification;
        });

        return response()->json([
            'status' => 'success',
            'data'   => $notifications
        ], 200);
    }
    public function showNotification($id)
{
    $notification = Notification::where('user_id', Auth::id())
        ->where('id', $id)
        ->first();

    if (!$notification) {
        return response()->json([
            'status' => 'error',
            'message' => 'Notification not found'
        ], 404);
    }
    if (!$notification->is_read) {
        $notification->update(['is_read' => true]);
    }

    return response()->json([
        'status' => 'success',
        'data' => $notification
    ]);
    }

}
