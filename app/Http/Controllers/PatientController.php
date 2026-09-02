<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentPatientRequest;
use App\Http\Requests\AppointmentUpdateRequest;
use App\Http\Requests\CreatePatientRequest;
use App\Http\Requests\PatientRequest;
use App\Http\Requests\PatientUpdateRequest;
use App\Mail\ReceptionEmail;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\NotificationService;
use App\Services\PatientService;
use App\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    protected $PatientService;
    protected $AppointmentService;

    use UploadFileTrait;
    public function __construct(PatientService $PatientService, AppointmentService $AppointmentService)
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
        // نحتفظ بالقيم القديمة قبل التحديث (الـ service بيعدل نفس الـ object في الذاكرة)
        $oldDate = $appointment->appointment_date;
        $oldStartTime = $appointment->start_time;

        $result = $this->AppointmentService->updateAppointment($appointment, $request->validated());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        }

        $updatedAppointment = $result['data'];

        $isTimeChanged = ($oldDate != $updatedAppointment->appointment_date) ||
            ($oldStartTime != $updatedAppointment->start_time);

        if ($isTimeChanged) {
            $this->notifyDoctorAndReception(
                $updatedAppointment,
                $request->user(),
                'تعديل موعد كشف',
                'تغيير موعد في العيادة',
                "قام المريض {$request->user()->name} بتغيير موعده إلى {$updatedAppointment->appointment_date}",
                "تم تعديل موعد المريض {$request->user()->name}"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الموعد بنجاح.',
            'data'    => $updatedAppointment
        ], 200);
    }
    public function appointmentCancel(Request $request, Appointment $appointment)
    {
        $result = $this->AppointmentService->cancelAppointment($appointment);

        if ($result['status_code'] === 200) {
            $this->notifyDoctorAndReception(
                $result['response']['data'],
                $request->user(),
                'إلغاء موعد كشف',
                'إلغاء موعد في العيادة',
                "قام المريض {$request->user()->name} بإلغاء موعده",
                "قام المريض {$request->user()->name} بإلغاء موعده"
            );
        }

        return response()->json($result['response'], $result['status_code']);
    }
    private function notifyDoctorAndReception(
        Appointment $appointment,
        $currentUser,
        string $doctorTitle,
        string $receptionTitle,
        string $doctorBody,
        string $receptionBody
    ) {
        // 1. إشعار الطبيب — لازم نجيب user_id تبعه، مش doctor_id مباشرة
        $doctorUserId = $appointment->doctor?->user_id;

        if ($doctorUserId) {
            NotificationService::sendToUser(
                $doctorUserId,
                $doctorTitle,
                $doctorBody,
                ['appointment_id' => $appointment->id]
            );
        }

        // 2. إشعار موظفي استقبال العيادة
        $clinicId = $appointment->clinic_id;

        if ($clinicId) {
            $receptionistUserIds = User::whereHas('reception', function ($query) use ($clinicId) {
                $query->where('clinic_id', $clinicId);
            })->pluck('id');

            foreach ($receptionistUserIds as $userId) {
                NotificationService::sendToUser(
                    $userId,
                    $receptionTitle,
                    $receptionBody,
                    ['appointment_id' => $appointment->id]
                );
            }
        }
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
        if (!$patient) {
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

        $appointment->load(['prescription.items', 'doctor.user', 'clinic', 'labOrders.tests']);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب تفاصيل الموعد بنجاح.',
            'data'    => $appointment
        ], 200);
    }
    public function storeAccountByReception(CreatePatientRequest $request)
    {
        $validated = $request->validated();

        $patient = DB::transaction(function () use ($validated) {
            // 1. إنشاء حساب المستخدم (Password يُشفّر تلقائياً عبر $casts في موديل User)
            $user = User::create([
                'name'        => $validated['name'],
                'last_name'   => $validated['last_name'],
                'email'       => $validated['email'],
                'phone'       => $validated['phone'],
                'password'    => $validated['password'],
                'is_verified' => true,
            ]);

            // إسناد Spatie Role
            $user->assignRole('patient');
            Mail::to($validated['email'])->send(new ReceptionEmail($validated['email'], $validated['password']));

            // 2. إنشاء ملف المريض (الـ qr_token يُولّد تلقائياً عبر boot Method)
            $patient = $user->patient()->create([
                'gender'           => $validated['gender'],
                'address'          => $validated['address'],
                'birth_date'       => $validated['birth_date'],
                'allergies'        => $validated['allergies'] ?? null,
                'hereditary'       => $validated['hereditary'] ?? null,
                'chronic_diseases' => $validated['chronic_diseases'] ?? null,
                'blood_type'       => $validated['blood_type'] ?? null,
                'taller'           => $validated['taller'] ?? null,
                'weight'           => $validated['weight'] ?? null,
            ]);

            return $patient;
        });

        // تحضير الرد مع تحميل بيانات الـ User
        $patient->load('user:id,name,last_name,email,phone');

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء ملف المريض بنجاح.',
            'data'    => $patient,
        ], 201);
    }
}
