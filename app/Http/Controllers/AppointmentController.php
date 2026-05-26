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

    // 2. جلب التاريخ من البيانات التي تم التحقق منها (تضمن عدم وجود null)
    $date = $validated['date'];

    // استدعاء الـ Logic من الـ Service
    $slots = $this->appointmentService->generateAvailableSlots((int)$doctorId, $date);

    if (empty($slots)) {
        return response()->json([
            'success' => false,
            'message' => 'لا يوجد دوام مسجل للطبيب في هذا اليوم، أو اليوم المحدد عطلة.'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'doctor_id' => (int)$doctorId,
            'date' => $date,
            'slots' => $slots
        ]
    ], 200);
}
}
