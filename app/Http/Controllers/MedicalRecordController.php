<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalRecordRequest;
use App\Models\Appointment;
use App\Models\HomeVisit;
use App\Models\MedicalRecord;
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
// قم بتغيير البارامتر الثاني ليكون متغير عادي $homevisit_id بدلاً من الموديل
    public function storePrescriptionHomeVisite(Request $request, $homevisit_id)
    {
        dd($homevisit_id);
        $user = Auth::user()->load('doctorProfile');

        // 1. التحقق من صلاحية الطبيب
        if (!$user->doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }
        $validatedData = $request->validate([
            'instructions'          => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.medicine_name' => 'required|string|max:255',
            'items.*.dosage'        => 'required|string|max:100',
            'items.*.frequency'     => 'required|string|max:100',
            'items.*.duration'      => 'required|string|max:100',
        ]);

        $homevisit = HomeVisit::findOrFail($homevisit_id);
        if (!$homevisit || in_array($homevisit->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إضافة روشتة لزيارة منزلية غير موجودة، منتهية أو ملغاة.'
            ], 422);
        }

        // 5. استدعاء الـ Service وإرسال الموديل بعد التأكد من جلب البيانات بنجاح
        $result = $this->medicalRecordService->storePrescriptionHomeVisit($homevisit, $validatedData);

        return response()->json($result['response'], $result['status_code']);
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
}
