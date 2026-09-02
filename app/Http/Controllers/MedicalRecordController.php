<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalRecordRequest;
use App\Http\Requests\UpdateMedicalRecordRequest;
use App\Http\Requests\UpdatePatientAllergiesRequest;
use App\Models\Appointment;
use App\Models\HomeVisit;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Services\MedicalRecordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $data['patient_id']     = $appointment->patient_id;
        $data['doctor_id']      = $doctorId;

        $record = $this->medicalRecordService->createRecord($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حفظ السجل الطبي للمريض بنجاح، وإغلاق الموعد.',
            'data'    => $record
        ], 201);
    }
    public function storeHomeVisitMedicalRecord(StoreMedicalRecordRequest $request, $homevisit_id)
    {
        $user = Auth::user()->load('doctorProfile');

        if (!$user->doctorProfile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }

        $doctorId = $user->doctorProfile->id;

        $homeVisit = HomeVisit::find($homevisit_id);

        if (!$homeVisit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'الزيارة المنزلية غير موجودة.'
            ], 404);
        }

        // التحقق من ملكية الزيارة للطبيب
        if ($homeVisit->doctor_id !== $doctorId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، هذه الزيارة غير مسجلة باسمك.'
            ], 403);
        }

        // التحقق من عدم وجود سجل مسبق
        if ($homeVisit->medicalRecord()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'تم إنشاء سجل طبي لهذه الزيارة مسبقاً، لا يمكن تكرار العملية.'
            ], 400);
        }

        $data = $request->validated();

        // حقن الـ IDs الموثوقة من الباك إند مباشرة
        $data['home_visit_id'] = $homeVisit->id;
        $data['patient_id']    = $homeVisit->patient_id;
        $data['doctor_id']     = $doctorId;

        $record = $this->medicalRecordService->createRecord($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حفظ السجل الطبي للزيارة المنزلية بنجاح.',
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
    public function storePrescriptionHomeVisite(Request $request, $homevisit_id)
    {
        $user = Auth::user()->load('doctorProfile');

        // 1. التحقق من صلاحية الطبيب
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

        // 3. جلب الزيارة يدوياً باستخدام الـ id الصريح القادم من الرابط
        $homevisit = HomeVisit::findOrFail($homevisit_id);
        if (!$homevisit || in_array($homevisit->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إضافة روشتة لزيارة منزلية غير موجودة، منتهية أو ملغاة.'
            ], 422);
        }

        // 5. استدعاء الـ Service وإرسال الموديل
        $result = $this->medicalRecordService->storePrescriptionHomeVisit($homevisit, $validatedData);

        return response()->json($result['response'], $result['status_code']);
    }
    public function getMyMedicalHistory()
    {
        // 1. جلب رقم المريض من التوكن بكل أمان
        $patientId = Auth::user()->patient->id;

        // 2. جلب السجلات الطبية مع العلاقات المطلوبة
        $history = MedicalRecord::with([
            'doctor.user:id,name',
            'appointment:id,appointment_date',
            'appointment.attachments',
        ])
        ->where('patient_id', $patientId)
        ->orderBy('created_at', 'desc')
        ->get();

        // 3. ترتيب البيانات وإرسالها
        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب السجل الطبي بنجاح',
            'data'    => $history
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

        $doctor = $user->doctorProfile;

        // التحقق من صلاحية الوصول (وجود موعد سابق أو حالي)
        $hasRelationship = Appointment::where('doctor_id', $doctor->id)
                                      ->where('patient_id', $patient->id)
                                      ->exists();

        if (!$hasRelationship) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، غير مصرح لك بالوصول إلى السجل الطبي لهذا المريض.'
            ], 403);
        }

        // جلب التاريخ المرضي المفلتر حسب اختصاص الطبيب من خلال الـ Service
        $history = $this->medicalRecordService->getPatientHistory($patient->id, $doctor->specialization_id);

        // تحسين مخرجات الـ JSON
        $formattedHistory = collect($history)->map(function ($record) {
            return [
                'id'              => $record->id,
                'appointment_id'  => $record->appointment_id,
                'patient_id'      => $record->patient_id,
                'diagnosis'       => $record->diagnosis,
                'chief_complaint' => $record->chief_complaint,
                'notes'           => $record->notes,
                'created_at'      => $record->created_at,

                'doctor' => [
                    'id'        => $record->doctor?->id,
                    'full_name' => ($record->doctor?->user?->name ?? '') . ' ' . ($record->doctor?->user?->last_name ?? ''),
                    'image'     => $record->doctor?->image,
                ],

                'appointment' => [
                    'id'               => $record->appointment?->id,
                    'appointment_date' => $record->appointment?->appointment_date,
                    'start_time'       => $record->appointment?->start_time,
                ],

                'vital_signs'   => $record->vitalSigns ?? null,
                'prescriptions' => $record->prescriptions ?? [],
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب التاريخ المرضي للمريض ضمن اختصاصك بنجاح.',
            'data'    => $formattedHistory
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

        // 2. التحقق من وجود علاقة بين الطبيب والمريض
        $hasRelationship = Appointment::where('doctor_id', $doctorId)
                                      ->where('patient_id', $patient->id)
                                      ->exists();

        if (!$hasRelationship) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، غير مصرح لك بتعديل البيانات الطبية لهذا المريض.'
            ], 403);
        }

        // 3. جلب البيانات المفحوصة
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

        // التحقق من ملكية الموعد للطبيب الحالي
        if ($appointment->doctor_id !== $doctorId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، هذا الموعد غير مسجل باسمك ولا يمكنك إضافة مؤشرات حيوية له.'
            ], 403);
        }

        // التحقق من البيانات القادمة
        $validated = $request->validate([
            'weight'         => 'nullable|integer|min:1',
            'taller'         => 'nullable|integer|min:1',
            'blood_pressure' => 'nullable|string',
            'temperature'    => 'nullable|numeric',
            'blood_sugar'    => 'nullable|string',
        ]);

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

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تسجيل وتحديث المؤشرات الحيوية للمريض بنجاح.',
            'data'    => [
                'appointment_id' => $appointment->id,
                'patient_id'     => $patient->id,
                'weight'         => $patient->weight,
                'taller'         => $patient->taller,
                'blood_pressure' => $validated['blood_pressure'] ?? null,
                'temperature'    => $validated['temperature'] ?? null,
                'blood_sugar'    => $validated['blood_sugar'] ?? null,
            ]
        ], 201);
    }
}
