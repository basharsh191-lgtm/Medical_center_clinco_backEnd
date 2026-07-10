<?php

namespace App\Http\Controllers;

use App\Http\Requests\DoctorRequest;
use App\Http\Requests\ReceptionRequest;
use App\Models\DeviceTokens;
use App\Models\User;
use App\Services\FcmService;
use App\Services\StaffService;
use App\UploadFileTrait;

class StaffController extends Controller
{
protected $staffService;
protected $fcmService;
use UploadFileTrait;

public function __construct(StaffService $staffService, FcmService $fcmService)
    {
        $this->staffService = $staffService;
        $this->fcmService = $fcmService;
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

    // إنشاء الطبيب
    $doctor = $this->staffService->createStaff(
        $validatedData,
        'doctor'
    );

    // ==========================================
    // كود إرسال الإشعار الجماعي
    // ==========================================

    // 1. جلب جميع التوكنات من قاعدة البيانات (نستبعد القيم الفارغة)
    // استبدل 'device_token' باسم العمود الفعلي لديك في الداتا بيز
    $tokens = DeviceTokens::whereNotNull('token')
                  ->pluck('token')
                  ->toArray();

    // 2. التحقق من وجود توكنات قبل محاولة الإرسال
    if (!empty($tokens)) {
        // يمكنك استخدام بيانات الطبيب المضاف في محتوى الإشعار
        $doctorName = $validatedData['name'] ?? 'طبيب جديد';
        $title = 'انضمام كادر طبي جديد! 👨‍⚕️';
        $body = "تم انضمام {$doctorName} إلى عيادتنا. احجز موعدك الآن!";

        $extraData = [
            'type' => 'new_doctor',
            'doctor_id' => (string) $doctor->id // يفضل تحويل الأرقام لنصوص في بيانات الإشعار
        ];

        // 3. استدعاء دالة الإرسال من السيرفيس
        $this->fcmService->sendMulticastNotification($tokens, $title, $body, $extraData);
    }
    return response()->json([
        'message' => 'Doctor created successfully',
        'data' => $doctor,
        'image_url' => $validatedData['image']
    ], 201);
}
}

