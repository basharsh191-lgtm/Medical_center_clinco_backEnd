<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\HomeVisit;
use App\Services\MedicalRecordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescriptionController extends Controller
{
    protected $medicalRecordService;

    public function __construct(MedicalRecordService $medicalRecordService)
    {
        $this->medicalRecordService = $medicalRecordService;
    }

    // ==========================================
    // 1. روشتات العيادة (Clinic Prescriptions)
    // ==========================================

    public function storePrescription(Request $request, Appointment $appointment)
    {
        $user = Auth::user()->load('doctorProfile');

        if (!$user->doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }

        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إضافة روشتة لموعد منتهي أو ملغي.'
            ], 422);
        }

        $validatedData = $request->validate([
            'instructions'           => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.medicine_name'  => 'required|string|max:255',
            'items.*.dosage'         => 'required|string|max:100',
            'items.*.frequency'      => 'required|string|max:100',
            'items.*.duration'       => 'required|string|max:100',
        ]);

        $result = $this->medicalRecordService->storePrescription($appointment, $validatedData);
        return response()->json($result['response'], $result['status_code']);
    }

    public function showPrescription($id)
    {
        $prescription = $this->medicalRecordService->getPrescription($id);

        return response()->json([
            'success' => true,
            'data'    => $prescription
        ]);
    }

    public function updatePrescription(Request $request, $id)
    {
        $validatedData = $request->validate([
            'instructions'          => 'nullable|string',
            'items'                 => 'sometimes|array|min:1',
            'items.*.medicine_name' => 'required|string|max:255',
            'items.*.dosage'        => 'required|string|max:100',
            'items.*.frequency'     => 'required|string|max:100',
            'items.*.duration'      => 'required|string|max:100',
        ]);

        $updatedPrescription = $this->medicalRecordService->updatePrescription($id, $validatedData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الروشتة بنجاح.',
            'data'    => $updatedPrescription
        ]);
    }

    public function destroyPrescription($id)
    {
        $this->medicalRecordService->deletePrescription($id);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الروشتة بنجاح.'
        ]);
    }

    // ==========================================
    // 2. روشتات الزيارات المنزلية (Home Visit Prescriptions)
    // ==========================================

    public function storePrescriptionHomeVisite(Request $request, $homevisit_id)
    {
        $user = Auth::user()->load('doctorProfile');

        if (!$user->doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }

        $validatedData = $request->validate([
            'instructions'           => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.medicine_name'  => 'required|string|max:255',
            'items.*.dosage'         => 'required|string|max:100',
            'items.*.frequency'      => 'required|string|max:100',
            'items.*.duration'       => 'required|string|max:100',
        ]);

        $homevisit = HomeVisit::find($homevisit_id);

        if (!$homevisit || in_array($homevisit->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إضافة روشتة لزيارة منزلية غير موجودة، منتهية أو ملغاة.'
            ], 422);
        }

        $result = $this->medicalRecordService->storePrescriptionHomeVisit($homevisit, $validatedData);

        return response()->json($result['response'], $result['status_code']);
    }

    public function showPrescriptionHomeVisit($homevisit_id)
    {
        $homevisit = HomeVisit::with('prescriptions.items')->find($homevisit_id);

        if (!$homevisit || !$homevisit->prescriptions->first()) {
            return response()->json([
                'success' => false,
                'message' => 'الروشتة غير موجودة لهذه الزيارة المنزلية.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $homevisit->prescriptions->first()
        ], 200);
    }

    public function updatePrescriptionHomeVisit(Request $request, $homevisit_id)
    {
        $user = Auth::user()->load('doctorProfile');

        if (!$user->doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }

        $homevisit = HomeVisit::find($homevisit_id);

        if (!$homevisit) {
            return response()->json([
                'success' => false,
                'message' => 'الزيارة المنزلية غير موجودة.'
            ], 404);
        }

        $validatedData = $request->validate([
            'instructions'           => 'nullable|string',
            'items'                  => 'sometimes|array|min:1',
            'items.*.medicine_name'  => 'required|string|max:255',
            'items.*.dosage'         => 'required|string|max:100',
            'items.*.frequency'      => 'required|string|max:100',
            'items.*.duration'       => 'required|string|max:100',
        ]);

        $result = $this->medicalRecordService->updatePrescriptionHomeVisit($homevisit, $validatedData);

        return response()->json($result['response'], $result['status_code']);
    }

    public function destroyPrescriptionHomeVisit($homevisit_id)
    {
        $user = Auth::user()->load('doctorProfile');

        if (!$user->doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }

        $homevisit = HomeVisit::find($homevisit_id);

        if (!$homevisit) {
            return response()->json([
                'success' => false,
                'message' => 'الزيارة المنزلية غير موجودة.'
            ], 404);
        }

        $result = $this->medicalRecordService->deletePrescriptionHomeVisit($homevisit);

        return response()->json($result['response'], $result['status_code']);
    }
}
