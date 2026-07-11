<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalRecordRequest;
use App\Http\Requests\UpdateMedicalRecordRequest;
use App\Http\Requests\UpdatePatientAllergiesRequest;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\patient;
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
    public function storeMedicalRecord(StoreMedicalRecordRequest $request, Appointment $appointment)
    {
        $user = Auth::user()->load('doctorProfile');

        if (!$user->doctorProfile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }

        $doctorId = $user->doctorProfile->id;

        // التحقق من ملكية الموعد للطبيب
        if ($appointment->doctor_id !== $doctorId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، هذا الموعد غير مسجل باسمك.'
            ], 403);
        }

        // التحقق من عدم وجود سجل مسبق
        if ($appointment->medicalRecord()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'تم إنشاء سجل طبي لهذا الموعد مسبقاً، لا يمكن تكرار العملية.'
            ], 400);
        }

        // جلب البيانات التي تم التحقق منها (التشخيص، الشكوى، الملاحظات)
        $data = $request->validated();

        // حقن الـ IDs الموثوقة من الباك إند مباشرة
        $data['appointment_id'] = $appointment->id;
        $data['patient_id'] = $appointment->patient_id;
        $data['doctor_id'] = $doctorId;

        $record = $this->medicalRecordService->createRecord($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حفظ السجل الطبي للمريض بنجاح، وإغلاق الموعد.',
            'data'    => $record
        ], 201);
    }
    public function updateMedicalRecord(UpdateMedicalRecordRequest $request, $id)
    {
        $user = Auth::user()->load('doctorProfile');

        // 1. التحقق من أن المستخدم طبيب
        if (!$user->doctorProfile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }
        $doctorId = $user->doctorProfile->id;
        $record = MedicalRecord::find($id);
        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، السجل الطبي غير موجود.'
            ], 404);
        }
        if ($record->doctor_id !== $doctorId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، لا تملك صلاحية تعديل هذا السجل الطبي لأنه غير مسجل باسمك.'
            ], 403);
        }
        $data = $request->validated();
        $record->update($data);
        return response()->json([
            'status'  => 'success',
            'message' => 'تم تعديل السجل الطبي بنجاح.',
            'data'    => $record
        ], 200);
    }
    public function destroyMedicalRecord($id)
    {
        $user = Auth::user()->load('doctorProfile');

        // 1. التحقق من أن المستخدم طبيب
        if (!$user->doctorProfile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }
        $doctorId = $user->doctorProfile->id;
        $record = MedicalRecord::find($id);
        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، السجل الطبي المُراد حذفه غير موجود مسبقاً.'
            ], 404);
        }
        if ($record->doctor_id !== $doctorId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، لا تملك صلاحية حذف هذا السجل الطبي لأنه غير مسجل باسمك.'
            ], 403);
        }
        $record->delete();
        return response()->json([
            'status'  => 'success',
            'message' => 'تم حذف السجل الطبي بنجاح.'
        ], 200);
    }
    public function getMyMedicalHistory()
    {
        // 1. جلب رقم المريض من التوكن بكل أمان
        $patientId = Auth::user()->patient->id;

        // 2. جلب السجلات الطبية مع العلاقات المطلوبة (Eager Loading)
        $history = MedicalRecord::with([
            'doctor.user:id,name', // جلب اسم الدكتور فقط لتخفيف حجم الداتا
            'appointment:id,appointment_date', // جلب تاريخ الموعد
            // إذا كنت رابط الوصفات أو المرفقات بالسجل أو بالموعد، بتجيبهم هون
            'appointment.attachments',
        ])
        ->where('patient_id', $patientId)
        ->orderBy('created_at', 'desc') // أحدث زيارة أولاً
        ->get();

        // 3. ترتيب البيانات وإرسالها للموبايل
        return response()->json([
            'status' => 'success',
            'message' => 'تم جلب السجل الطبي بنجاح',
            'data' => $history
        ]);
    }
    public function getPatientHistory(Patient $patient)
        {
            $user = Auth::user()->load('doctorProfile');

            if (!$user->doctorProfile) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
                ], 403);
            }

            $doctorId = $user->doctorProfile->id;

            // استخراج معرّف الاختصاص (أو العيادة) للطبيب الذي قام بالطلب
            $specializationId = $user->doctorProfile->specialization_id;

            // التحقق من أن هذا الطبيب له علاقة بهذا المريض (لحماية البيانات)
            $hasRelationship = Appointment::where('doctor_id', $doctorId)
                                        ->where('patient_id', $patient->id)
                                        ->exists();

            if (!$hasRelationship) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'عذراً، غير مصرح لك بالوصول إلى السجل الطبي لهذا المريض.'
                ], 403);
            }

            // تمرير معرّف المريض ومعرّف الاختصاص إلى الـ Service
            $history = $this->medicalRecordService->getPatientHistory($patient->id, $specializationId);

            return response()->json([
                'status'  => 'success',
                'message' => 'تم جلب التاريخ المرضي للمريض ضمن اختصاصك بنجاح.',
                'data'    => $history
            ], 200);
    }
    public function getPatientAllergies(Patient $patient)
    {
        $user = Auth::user()->load('doctorProfile');

        if (!$user->doctorProfile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }
        $allergiesData = $this->medicalRecordService->getPatientAllergies($patient->id);
        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب التحسسات الدوائية والمعلومات الحيوية بنجاح.',
            'data'    => $allergiesData
            ], 200);
    }
    public function updatePatientAllergies(UpdatePatientAllergiesRequest $request, Patient $patient)
    {
        $user = Auth::user()->load('doctorProfile');

        // 1. التحقق من أن المستخدم طبيب
        if (!$user->doctorProfile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }

        $doctorId = $user->doctorProfile->id;

        // 2. حماية البيانات: التحقق من وجود علاقة (موعد) بين الطبيب والمريض لمنع التعديل العشوائي
        $hasRelationship = Appointment::where('doctor_id', $doctorId)
                                      ->where('patient_id', $patient->id)
                                      ->exists();

        if (!$hasRelationship) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، غير مصرح لك بتعديل البيانات الطبية لهذا المريض.'
            ], 403);
        }

        // 3. جلب البيانات التي تم التحقق منها من الـ Request
        $data = $request->validated();

        // 4. استدعاء الـ Service لتنفيذ التعديل
        $updatedData = $this->medicalRecordService->updatePatientAllergies($patient->id, $data);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تحديث التحسسات الدوائية والمعلومات الحيوية للمريض بنجاح.',
            'data'    => $updatedData
        ], 200);
    }
    public function storeVitalSigns(Request $request, Appointment $appointment)
    {
        $user = Auth::user()->load('doctorProfile');

        // التحقق من أن المستخدم طبيب
        if (!$user->doctorProfile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }

        $doctorId = $user->doctorProfile->id;

        // التحقق من ملكية الموعد للطبيب الحالي لضمان الأمن
        if ($appointment->doctor_id !== $doctorId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، هذا الموعد غير مسجل باسمك ولا يمكنك إضافة مؤشرات حيوية له.'
            ], 403);
        }

        // التحقق من البيانات القادمة من الريكويست
        $validated = $request->validate([
            'weight' => 'nullable|integer|min:1',
            'taller' => 'nullable|integer|min:1', // الطول
            // إذا كنت قد أضفت حقول الضغط والحرارة وال سكر إلى جدول المريض أو جدول منفصل، يمكنك التحقق منها هنا:
            'blood_pressure' => 'nullable|string',
            'temperature'    => 'nullable|numeric',
            'blood_sugar'    => 'nullable|string',
        ]);

        // جلب المريض المرتبط بالموعد لتحديث بياناته الحيوية الثابتة
        $patient = $appointment->patient;

        if (!$patient) {
            return response()->json([
                'status'  => 'error',
                'message' => 'المريض المرتبط بهذا الموعد غير موجود.'
            ], 404);
        }

        // تحديث الحقول الحيوية الأساسية للمريض في جدول patients
        $patient->update([
            'weight' => $validated['weight'] ?? $patient->weight,
            'taller' => $validated['taller'] ?? $patient->taller,
        ]);

        // هنا: إذا كان لديك جدول خاص بالـ Vital Signs التاريخية لكل موعد، يمكنك إدخال السجل عبر السيرفيس:
        // $vitalSignRecord = $this->medicalRecordService->storeAppointmentVitals($appointment->id, $validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تسجيل وتحديث المؤشرات الحيوية للمريض بنجاح.',
            'data'    => [
                'appointment_id' => $appointment->id,
                'patient_id'     => $patient->id,
                'weight'         => $patient->weight,
                'taller'         => $patient->taller,
                // يمكنك إرجاع الحقول الإضافية إذا تم حفظها في مكان آخر
                'blood_pressure' => $validated['blood_pressure'] ?? null,
                'temperature'    => $validated['temperature'] ?? null,
                'blood_sugar'    => $validated['blood_sugar'] ?? null,
            ]
        ], 201);
    }

}
