<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    protected $appointmentService;

    // حقن الـ Service داخل الـ Constructor
    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * جلب الفترات الزمنية المتاحة والمحجوزة لطبيب معين في تاريخ محدد
     */
public function getAvailableSlots(Request $request, $doctorId): JsonResponse
{
    // 1. التحقق من المدخلات وحفظ البيانات الموثوقة في مصفوفة
    $validated = $request->validate([
        'date' => 'required|date_format:Y-m-d|after_or_equal:today',
    ]);

    // 2. جلب التاريخ من البيانات التي تم التحقق منها
    $date = $validated['date'];

    // استدعاء الـ Logic من الـ Service
    $result = $this->appointmentService->generateAvailableSlots((int)$doctorId, $date);

    // إذا كانت النتيجة فارغة (لم يجد جدول)
    if (empty($result)) {
        return response()->json([
            'success' => false,
            'message' => 'لا يوجد دوام مسجل للطبيب في هذا اليوم، أو اليوم المحدد عطلة.'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'doctor_id'     => (int)$doctorId,
            'date'          => $date,
            'schedule_type' => $result['schedule_type'], // إرسال نوع الجدول هنا (clinic, home_visit, both)
            'slots'         => $result['slots']
        ]
    ], 200);
}
}
