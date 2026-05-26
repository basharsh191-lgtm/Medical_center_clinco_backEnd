<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Doctor;
use App\Services\DoctorScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReceptionistScheduleController extends Controller
{
    protected $scheduleService;

    public function __construct(DoctorScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ], 422);
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
}
