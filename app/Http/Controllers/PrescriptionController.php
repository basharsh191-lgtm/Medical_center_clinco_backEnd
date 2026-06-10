<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\MedicalRecordService;
use Auth;
use Illuminate\Http\Request;
use Validator;

class PrescriptionController extends Controller
{
    protected $medicalRecordService;

    public function __construct(MedicalRecordService $medicalRecordService)
    {
        $this->medicalRecordService = $medicalRecordService;
    }
    public function storePrescription(Request $request, Appointment $appointment)
    {
        $user = Auth::user()->load('doctorProfile');

        // 1. التحقق من صلاحية الطبيب (توحيد شكل الاستجابة)
        if (!$user->doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }

        // 2. التحقق من حالة الموعد
        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إضافة روشتة لموعد منتهي أو ملغي.'
            ], 422);
        }

        // 3. التحقق من صحة البيانات (Laravel سيعيد 422 تلقائياً مع الأخطاء إذا فشل التحقق)
        $validatedData = $request->validate([
            'instructions'          => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.medicine_name' => 'required|string|max:255',
            'items.*.dosage'        => 'required|string|max:100',
            'items.*.frequency'     => 'required|string|max:100',
            'items.*.duration'      => 'required|string|max:100',
        ]);

        // 4. استدعاء الخدمة وتمرير البيانات النظيفة (Validated)
        $result = $this->medicalRecordService->storePrescription($appointment, $validatedData);

        return response()->json($result['response'], $result['status_code']);
    }
    public function getAppointmentPrescription(Appointment $appointment)
    {
        // استدعاء اللوجيك من الـ Service
        $result = $this->medicalRecordService->getPrescriptionByAppointment($appointment);

        return response()->json($result['response'], $result['status_code']);
    }
}
