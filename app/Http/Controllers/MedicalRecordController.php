<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalRecordRequest;
use App\Models\Appointment;
use App\Services\MedicalRecordService;
use Auth;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    protected $medicalRecordService;

    public function __construct(MedicalRecordService $medicalRecordService)
    {
        $this->medicalRecordService = $medicalRecordService;
    }
    public function storeMedicalRecord(StoreMedicalRecordRequest $request)
    {
        $user = Auth::user()->load('doctorProfile');

        if (!$user->doctorProfile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }
        $data = $request->validated();
        $doctorId = $user->doctorProfile->id;
        $appointment = Appointment::find($data['appointment_id']);
        if (!$appointment || $appointment->doctor_id !== $doctorId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، هذا الموعد غير مسجل باسمك أو غير موجود.'
            ], 403);
        }
        if (isset($data['patient_id']) && $appointment->patient_id !== (int)$data['patient_id']) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، هذا المريض ليس هو الشخص المحجوز له في هذا الموعد.'
            ], 422); // 422 Unprocessable Entity
        }
        if ($appointment->medicalRecord()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'تم إنشاء سجل طبي لهذا الموعد مسبقاً، لا يمكن تكرار العملية.'
            ], 400);
        }
        $data['patient_id'] = $appointment->patient_id;
        $data['doctor_id'] = $doctorId;

        $record = $this->medicalRecordService->createRecord($data, $appointment);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حفظ السجل الطبي للمريض بنجاح، وإغلاق الموعد.',
            'data'    => $record
        ], 201);
    }
}
