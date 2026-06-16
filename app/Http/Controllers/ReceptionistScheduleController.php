<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Services\DoctorScheduleService;
use App\Services\ReceptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceptionistScheduleController extends Controller
{
    protected $scheduleService;
    protected $checkInService;

    public function __construct(DoctorScheduleService $scheduleService, ReceptionService $checkInService)
    {
        $this->scheduleService = $scheduleService;
        $this->checkInService = $checkInService;
    }
public function storeSchedule(StoreScheduleRequest $request)
    {
        $validated = $request->validated();
        try {
            $schedule = $this->scheduleService->createOrUpdateSchedule($validated);
            return response()->json([
                'success' => true,
                'message' => 'تم تحديد مواعيد دوام الطبيب بنجاح.',
                'data'    => $schedule
            ], 201);
        } catch (\Exception $e) {
            // استخدام الكود 403 أو 422 حسب نوع الخطأ المرمي من الخدمة
            $statusCode = $e->getCode() >= 400 ? $e->getCode() : 422;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ], $statusCode);
        }
    }
    public function getMyClinicDoctors()
    {
        try {
            $doctors = $this->scheduleService->getClinicDoctors();
            $formattedDoctors = $doctors->map(function ($doctor) {
                return [
                    'doctor_id'      => $doctor->id,
                    'name'           => $doctor->user->name,
                    'last_name'      =>$doctor->user->last_name,
                    'specialization_id' => $doctor->specialization_id,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'تم جلب أطباء العيادة بنجاح.',
                'data'    => $formattedDoctors
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ], 403);
        }
    }
    public function checkInByPatientQR(Request $request)
        {
            $request->validate([
                'qr_token' => 'required|uuid|exists:patients,qr_token',
            ]);
            $user = Auth::user()->load('reception');
            if (!$user->reception) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'هذا المستخدم ليس موظف استقبال أو غير مرتبط بعيادة.'
                ], 400);
            }
            $receptionistClinicId = $user->reception->clinic_id;
            $appointment = $this->checkInService->checkInByQr($request->qr_token, $receptionistClinicId);
            return response()->json([
                'status' => 'success',
                'message' => 'تم تسجيل وصول المريض بنجاح وتحديث الحالة.',
            ]);
    }
}
