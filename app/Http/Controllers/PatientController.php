<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentPatientRequest;
use App\Http\Requests\AppointmentUpdateRequest;
use App\Http\Requests\PatientRequest;
use App\Models\Appointment;
use App\Models\patient;
use App\Services\AppointmentService;
use App\Services\PatientService;
use App\UploadFileTrait;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Str;

class PatientController extends Controller
{
protected $PatientService;
protected $AppointmentService;

use UploadFileTrait;
public function __construct(PatientService $PatientService , AppointmentService $AppointmentService)
    {
        $this->PatientService = $PatientService;
        $this->AppointmentService = $AppointmentService;
}
public function storePatient(PatientRequest $request)
{
    $validated = $request->validated();
    $patient = $this->PatientService->createPatient($validated);
    if (!$patient) {
        return response()->json([
            'success' => false,
            'message' => 'هذا المستخدم لديه حساب مريض بالفعل.'
        ], 422);
    }
    return response()->json([
        'success' => true,
        'message' => 'تم إنشاء ملف المريض بنجاح',
        'data' => $patient,
    ], 201);
}
public function showPatient()
{
    return $this->PatientService->getMyProfile();
}
//طريقة جديدة لل clean code
public function appointmentStore(AppointmentPatientRequest $request)
{
    $appointment = Appointment::create($request->validated());

    return response()->json([
        'success' => true,
        'message' => 'تم حجز الموعد بنجاح.',
        'data'    => $appointment
    ], 201);
}
public function appointmentUpdate(AppointmentUpdateRequest $request, Appointment $appointment)
{
    $result = $this->AppointmentService->updateAppointment($appointment, $request->validated());

    if (!$result['success']) {
        return response()->json(['success' => false, 'message' => $result['message']], 422);
    }

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث الموعد بنجاح.',
        'data'    => $result['data']
    ], 200);
}
public function appointmentCancel(Appointment $appointment)
{
    $result = $this->AppointmentService->cancelAppointment($appointment);

    return response()->json($result['response'], $result['status_code']);
}
public function patientAppointments()
    {
        $result = $this->PatientService->getAllAppointments();
        return response()->json($result['response'], $result['status_code']);
    }
    public function getMyQrData()
    {
        $patient = Auth::user()->patient;

        if (!$patient->qr_token) {
            $patient->update(['qr_token' => Str::uuid()]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب بيانات الـ QR بنجاح',
            'data'    => [
            'qr_string' => $patient->qr_token
            ]
        ]);
    }
}


