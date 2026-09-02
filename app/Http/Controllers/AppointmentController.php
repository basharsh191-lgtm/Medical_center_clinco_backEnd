<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\WalkInAppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    protected $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    public function getAvailableSlots(Request $request, $doctorId): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);

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
    public function storeWalkIn(WalkInAppointmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $today = Carbon::today()->toDateString();

        $appointment = DB::transaction(function () use ($validated, $today) {
            return Appointment::create([
                'patient_id'       => $validated['patient_id'],
                'doctor_id'        => $validated['doctor_id'],
                'clinic_id'        => $validated['clinic_id'],
                'appointment_date' => $today,
                'start_time'       => $validated['start_time'],
                'end_time'         => $validated['end_time'],
                'status'           => 'arrived',
                'notes'            => $validated['notes'] ?? null,
            ]);
        });

        $appointment->load([
            'patient.user:id,name,last_name,phone',
            'doctor.user:id,name,last_name',
            'clinic:id,clinic_name'
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تسجيل الموعد المباشر وتسجيل وصول المريض بنجاح.',
            'data'    => $appointment,
        ], 201);
    }
}
