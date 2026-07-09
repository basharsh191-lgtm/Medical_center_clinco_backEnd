<?php

namespace App\Http\Controllers;

use App\Http\Requests\DoctorRequest;
use App\Http\Requests\ReceptionRequest;
use App\Models\User;
use App\Services\StaffService;
use App\UploadFileTrait;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Notification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class StaffController extends Controller
{
protected $staffService;
protected $notificationService;
use UploadFileTrait;

public function __construct(StaffService $staffService, FirebaseNotificationService $notificationService)
    {
        $this->staffService = $staffService;
        $this->notificationService = $notificationService;
    }
public function storeReception(ReceptionRequest $request)
{
    $reception = $this->staffService->createStaff(
        $request->validated(),
        'reception'
    );

    return response()->json([
        'message' => 'Reception created successfully',
        'data' => $reception
    ], 201);
}
public function storeDoctor(DoctorRequest $request)
{
    $validatedData = $request->validated();
    if ($request->hasFile('image')) {
        $path = $this->upload($request->file('image'), 'doctor_picture', 'public');
        $validatedData['image'] = url('storage/' . $path);
    } else {
        $validatedData['image'] = null;
    }
    $doctor = $this->staffService->createStaff(
        $validatedData,
        'doctor'
    );

    return response()->json([
        'message' => 'Doctor created successfully',
        'data' => $doctor,
        'image_url' => $validatedData['image']
    ], 201);
}
public function sendTestNotification()
    {
        try {
            // 1. تحديد مسار ملف الـ json الذي قمنا بتحميله
            $path = storage_path('app/firebase/fcm.json');

            if (!file_exists($path)) {
                return response()->json(['error' => 'ملف fcm.json غير موجود في المسار المحدد!'], 404);
            }

            // 2. ربط الخدمة مع الفايربيز
            $factory = (new Factory())->withServiceAccount($path);
            $messaging = $factory->createMessaging();

            // 3. ضع هنا الـ Device Token الذي أعطاك إياه الفرونت إند للتجربة
            $deviceToken = 'erlXMXT1REWrAP6upJEE1w:APA91bHzSbLKlk7eLtEnTfu
            9DsEEaihdcOiFpeHfbyEQmDijlKsVX00NUZFYNuH2dZHOIwNociD9O_EMXFdjvbzHevk_
            2KltYZN6ALOX3OM33UO67Qlrwlk';

            // 4. بناء نص ومحتوى الإشعار
// ⚠️ اكتب المسار بالكامل هكذا ليتعرف عليه كـ Firebase Notification وليس Laravel Notification
$notification = \Kreait\Firebase\Messaging\Notification::create('تجربة إشعار', 'مرحباً، هذا أول إشعار ناجح من الصفر! 🎉');
            $message = CloudMessage::new()
                ->withTarget('token', $deviceToken)
                ->withNotification($notification)
                ->withData(['click_action' => 'FLUTTER_NOTIFICATION_CLICK']); // مهمة لبعض تطبيقات الفلاتر لفتح الإشعار

            // 5. الأمر الفعلي للإرسال
            $messaging->send($message);

            return response()->json(['success' => true, 'message' => 'تم إرسال الإشعار بنجاح!']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

