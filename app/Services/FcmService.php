<?php

namespace App\Services;

use App\Models\DeviceTokens;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\Notification as ModelsNotification;
use Kreait\Firebase\Factory;

class FcmService
{
    /**
     * إرسال إشعار عبر Firebase
     *
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param array $data (اختياري) لإرسال بيانات إضافية مع الإشعار
     * @return array
     */

    protected $messaging;

    public function __construct()
    {
        // 1. جلب المسار المكتوب في الـ .env عبر الـ config
        $credentials = config('services.firebase.credentials');

        // 2. دمج المسار مع جذر المشروع باستخدام base_path لضمان صيغة المسار المطلق
        $path = ($credentials && file_exists(base_path($credentials)))
            ? base_path($credentials)
            : storage_path('app/firebase/fcm.json'); // مسار احتياطي في حال الـ null

        // 3. بناء الفاكتوري بالمسار الصحيح
        $factory = (new Factory())->withServiceAccount($path);

        // 4. 🚨 السطر الأهم لتعريف الخدمة وتجنب الـ TypeError 🚨
        $this->messaging = $factory->createMessaging();
    }
    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        $messaging = app('firebase.messaging');

        // تجهيز الإشعار
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(Notification::create($title, $body));

        // إضافة بيانات إضافية (Payload) في حال وجودها
        if (!empty($data)) {
            $message = $message->withData($data);
        }

        try {
            // إرسال الإشعار
            $messaging->send($message);
            return [
                'status' => 'success',
                'message' => 'Notification sent successfully!'
            ];
        } catch (\Exception $e) {
            // يمكنك أيضاً تسجيل الخطأ هنا في ملفات الـ Log
            // \Log::error('FCM Error: ' . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    public function sendMulticastNotification(array $deviceTokens, $title, $body, $data = [])
    {
        $messaging = app('firebase.messaging');

        // تجهيز الإشعار (ملاحظة: لا نحدد الهدف هنا لأننا سنمرره في دالة الإرسال)
        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body));

        if (!empty($data)) {
            $message = $message->withData($data);
        }

        try {
            // إرسال الإشعار لجميع التوكنات
            $report = $messaging->sendMulticast($message, $deviceTokens);

            return [
                'status' => 'success',
                'message' => 'تم إرسال الإشعارات بنجاح',
                'successful_sends' => $report->successes()->count(),
                'failed_sends' => $report->failures()->count()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    public function sendToUser(int $userID, string $title, string $body, array $data = [])
    {
        try {
            $tokens = DeviceTokens::where('user_id', $userID)->pluck('token')->toArray();
            if (empty($tokens)) {
                Log::warning("No device tokens found for user ID: $userID");
                return ['success' => false, 'message' => 'No device tokens found'];
            }
            $notification = ModelsNotification::create([
                'user_id' => $userID,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'is_read' => false
            ]);
            Log::info("Notification saved to database with ID: " . $notification->id);

            // $firebaseNotification=FacadesNotification::create($title,$body);
            $firebaseNotification = \Kreait\Firebase\Messaging\Notification::create($title, $body);
            $message = CloudMessage::new()
                ->withNotification($firebaseNotification)
                ->withData($data);
            $successCount = 0;
            $failedCount = 0;

            foreach ($tokens as $token) {
                try {
                    $messageToSend = $message->withChangedTarget('token', $token);
                    $this->messaging->send($messageToSend);
                    $successCount++;
                    Log::info("Notification sent successfully to token: " . substr($token, 0, 20) . "...");
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Failed to send notification to token: " . substr($token, 0, 20) . "... Error: " . $e->getMessage());
                }
            }
            Log::info("Notification delivery result - Success: $successCount, Failed: $failedCount");
            return [
                'success' => true,
                'database_id' => $notification->id,
                'sent' => $successCount,
                'failed' => $failedCount
            ];
        } catch (\Exception $e) {
            Log::error('Failed to process notification: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
