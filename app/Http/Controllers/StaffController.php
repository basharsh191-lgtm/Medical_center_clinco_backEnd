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
public function storeDoctor(DoctorRequest $request,FcmService $fcmService)
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

 // 2. تجهيز بيانات الإشعار
    $title = 'انضم إلينا طبيب جديد! 🌟';
    $body = "نرحب بالدكتور {$doctor->name} (اختصاص {$doctor->specialty}) في المنصة.";

    // ميزة الـ Data Payload عشان مبرمج الفلاتر يفتح صفحة الدكتور فوراً عند الضغط على الإشعار
    $data = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'action' => 'OPEN_DOCTOR_PROFILE',
        'doctor_id' => (string) $doctor->id,
    ];

    // 3. إرسال الإشعار لكل اليوزرات اللي عندهم أجهزة مسجلة (على دفعات لحماية الذاكرة)
    // نختار فقط المستخدمين الذين يملكون توكنات في جدول الـ device_tokens
    User::whereHas('deviceTokens')->select('id')->chunk(100, function ($users) use ($fcmService, $title, $body, $data) {
        foreach ($users as $user) {
            // استدعاء الدالة المجهزة عندك بالـ Service
            $fcmService->sendToUser($user->id, $title, $body, $data);
        }
    });

    return response()->json([
        'message' => 'Doctor created successfully',
        'data' => $doctor,
        'image_url' => $validatedData['image']
    ], 201);
}
}

