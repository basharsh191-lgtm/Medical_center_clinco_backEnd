<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

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
}
