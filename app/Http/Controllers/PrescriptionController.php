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

        $validatedData = $request->validate([
            'instructions'          => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.medicine_name' => 'required|string|max:255',
            'items.*.dosage'        => 'required|string|max:100',
            'items.*.frequency'     => 'required|string|max:100',
            'items.*.duration'      => 'required|string|max:100',
        ]);
        $result = $this->medicalRecordService->storePrescription($appointment, $validatedData);
        return response()->json($result['response'], $result['status_code']);
    }
    // عرض روشتة محددة
    public function showPrescription($id)
    {
        $prescription = $this->medicalRecordService->getPrescription($id);
        return response()->json(['success' => true, 'data' => $prescription]);
    }
    public function updatePrescription(Request $request, $id)
    {
        $validatedData = $request->validate([
            'instructions' => 'nullable|string',
            'items'        => 'required|array|min:1',
        ]);

        $updatedPrescription = $this->medicalRecordService->updatePrescription($id, $validatedData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الروشتة بنجاح.',
            'data' => $updatedPrescription
        ]);
    }
    public function destroyPrescription($id)
    {
        $this->medicalRecordService->deletePrescription($id);
        return response()->json(['success' => true, 'message' => 'تم حذف الروشتة وإعادة فتح الموعد.']);
    }
}
