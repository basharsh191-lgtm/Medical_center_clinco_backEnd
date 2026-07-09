<?php
namespace App\Services;
use App\Models\DeviceTokens;
use App\Models\User;

use App\Models\DeviceToken;

use Kreait\Firebase\Messaging\CloudMessage;

use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
class PushNotifications
{
    public function sendNotificationToUser(User $user, $title, $body, $data = [])

{

    // 1. حفظ الإشعار في قاعدة البيانات (ليظهر للمستخدم داخل التطبيق في أيقونة الإشعارات)

    $user->notifications()->create([

        'title' => $title,

        'body'  => $body,

        'data'  => $data, // أي بيانات إضافية (مثل ID الموعد)

    ]);



    // 2. جلب جميع توكنات الأجهزة الخاصة بهذا المستخدم

    // نستخدم pluck لنجلب مصفوفة تحتوي على التوكنات فقط ['token1', 'token2']

    $tokens = $user->deviceTokens()->pluck('token')->toArray();



    // إذا لم يكن لديه أجهزة مسجلة، نوقف العملية هنا (لا داعي لإرسال شيء لفايربيس)

    if (empty($tokens)) {

        return false;

    }



    // 3. تجهيز طرد فايربيس (Payload)

    $messaging = app('firebase.messaging');

    $message = CloudMessage::new()

        ->withNotification(FirebaseNotification::create($title, $body))

        ->withData($data);



    // 4. إرسال الإشعار لجميع أجهزة المستخدم دفعة واحدة (Multicast)

    $report = $messaging->sendMulticast($message, $tokens);



    // 5. خطوة احترافية: تنظيف التوكنات الميتة (Dead Tokens)

    // إذا قام المستخدم بحذف التطبيق، فايربيس سيخبرنا أن التوكن فشل ويجب حذفه

    if ($report->hasFailures()) {

        $invalidTokens = $report->invalidTokens(); // استخراج التوكنات غير الصالحة



        if (!empty($invalidTokens)) {

            // حذفها من قاعدة البيانات الخاصة بك لتنظيفها

            DeviceTokens::whereIn('token', $invalidTokens)->delete();

        }

    }



    return true;

}
}
