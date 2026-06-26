<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentPatientRequest;
use App\Http\Requests\AppointmentUpdateRequest;
use App\Http\Requests\PatientRequest;
use App\Http\Requests\PatientUpdateRequest;
use App\Models\Appointment;
use App\Models\Doctor;
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
public function updatePatient(PatientUpdateRequest $request)
{
    $patient = Patient::where('user_id', Auth::id())->first();

    if (!$patient) {
        return response()->json([
            'success' => false,
            'message' => 'لم يتم العثور على ملف مريض لهذا الحساب.'
        ], 404);
    }

    $validated = $request->validated();
    $updatedPatient = $this->PatientService->updatePatient($patient, $validated);

    if (!$updatedPatient) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث البيانات، يرجى المحاولة لاحقاً.'
        ], 500);
    }

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث ملفك الشخصي بنجاح',
        'data'    => $updatedPatient,
    ], 200);
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
public function patientAppointments(){
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
// 1. جلب قائمة الأطباء المفضلين
public function getFavorites()
{
    $patient = Patient::where('user_id', Auth::id())->first();

    if (!$patient) {
        return response()->json(['success' => false, 'message' => 'ملف المريض غير موجود.'], 404);
    }

    $doctors = $this->PatientService->getFavoriteDoctors($patient);

    return response()->json([
        'success' => true,
        'message' => 'تم جلب قائمة الأطباء المفضلين بنجاح.',
        'data'    => $doctors
    ], 200);
}
public function toggleFavorite(Doctor $doctor)
{
    $patient = Patient::where('user_id', Auth::id())->first();
    if (!$patient)
    {
        return response()->json(['success' => false, 'message' => 'ملف المريض غير موجود.'], 404);
    }

    $result = $this->PatientService->toggleDoctorFavorite($patient, $doctor);

    return response()->json($result['response'], $result['status_code']);
}
public function getNextAppointment()
{
    $patient = Patient::where('user_id', Auth::id())->first();

    if (!$patient) {
        return response()->json([
            'success' => false,
            'message' => 'ملف المريض غير موجود.'
        ], 404);
    }

    $result = $this->PatientService->getNextAppointment($patient);

    return response()->json($result['response'], $result['status_code']);
}
public function showAppointment(Appointment $appointment)
    {
        $user = Auth::user();

        if ($user->doctorProfile) {
            // إذا كان المستخدم طبيب
            if ($appointment->doctor_id !== $user->doctorProfile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بعرض هذا الموعد.'
                ], 403);
            }
        } else {
            // إذا كان المستخدم مريض
            if ($appointment->patient_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بعرض تفاصيل هذا الموعد.'
                ], 403);
            }
        }

        $appointment->load(['prescription.items','doctor.user','clinic']);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب تفاصيل الموعد بنجاح.',
            'data'    => $appointment
        ], 200);
    }
}


