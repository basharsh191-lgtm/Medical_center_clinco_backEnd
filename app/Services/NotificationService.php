<?php

namespace App\Services;

use App\Models\DeviceTokens;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public static function sendToUser(int $userId, string $title, string $body, array $data = [])
    {
        // 1. حفظ الإشعار في قاعدة البيانات ليظهر في الـ API getNotifications
        $notification = Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
            'is_read' => false,
        ]);

        // 2. جلب جميع التوكنز المسجلة للمستخدم (قد يكون مسجل دخوله من أكثر من جهاز)
        $tokens = DeviceTokens::where('user_id', $userId)->pluck('token')->toArray();

        if (!empty($tokens)) {
            self::sendFcmNotification($tokens, $title, $body, $data);
        }

        return $notification;
    }
    private static function sendFcmNotification(array $tokens, string $title, string $body, array $data = [])
    {
        $serverKey = config('services.fcm.server_key'); // قم بضبطه في config/services.php

        if (!$serverKey) {
            Log::warning('FCM Server Key is missing in config.');
            return;
        }

        // تحويل أي قيم داخل الـ data إلى strings لتجنب مشاكل FCM
        $formattedData = array_map(fn($value) => is_array($value) ? json_encode($value) : (string)$value, $data);

        foreach ($tokens as $token) {
            Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                ],
                'data' => $formattedData,
            ]);
        }
    }
}
