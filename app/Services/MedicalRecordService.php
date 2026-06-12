<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderTest;
use App\Models\patient;
use App\Models\Prescription;
use Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;

class MedicalRecordService
{
    public function createRecord(array $data): MedicalRecord
    {
        return DB::transaction(function () use ($data) {
            $record = MedicalRecord::create($data);
            $appointment = Appointment::find($data['appointment_id']);
            if ($appointment) {
                $appointment->update(['status' => 'completed']);
            }
            return $record;
        });
    }
    public function storePrescription(Appointment $appointment, array $data): array
    {
        // استخدام try-catch لحماية الـ Transaction في حال فشل أي عملية
        try {
            DB::beginTransaction();

            // 1. إنشاء رأس الوصفة الطبية (Prescription)
            $prescription = Prescription::create([
                'appointment_id' => $appointment->id,
                'instructions'   => $data['instructions'] ?? null,
            ]);

            // 2. إدخال عناصر الأدوية دفعة واحدة (Bulk Insert) باستخدام createMany لرفع الأداء
            if (!empty($data['items'])) {
                $prescription->items()->createMany($data['items']);
            }

            // 3. تحويل حالة الموعد تلقائياً إلى مكتمل
            $appointment->update(['status' => 'completed']);

            DB::commit();

            return [
                'status_code' => 201,
                'response' => [
                    'success' => true,
                    'message' => 'تم تسجيل الروشتة الطبية وإنهاء الجلسة بنجاح.',
                    'data'    => [
                        'prescription_id' => $prescription->id
                    ]
                ]
            ];

        } catch (\Exception $e) {
            // في حال حدوث أي خطأ، تراجع عن كل عمليات الإدخال السابقة
            DB::rollBack();

            return [
                'status_code' => 500,
                'response' => [
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حفظ الروشتة، يرجى المحاولة لاحقاً.',
                    'error'   => $e->getMessage() // (اختياري) مفيد لك أثناء التطوير لمعرفة سبب الخطأ
                ]
            ];
        }
    }
    public function getPrescriptionByAppointment(Appointment $appointment)
    {
        $user = Auth::user();
        $patient = patient::where('user_id', $user->id)->first();

        if (!$patient || $appointment->patient_id !== $patient->id) {
            return [
                'status_code' => 403,
                'response'    => [
                    'success' => false,
                    'message' => 'غير مصرح لك باستعراض بيانات هذا الموعد.'
                ]
            ];
        }

        $prescription = Prescription::with(['items:id,prescription_id,medicine_name,dosage,frequency,duration'])
            ->where('appointment_id', $appointment->id)
            ->first();

        // 3. إذا كان الموعد لا يحتوي على روشتة بعد (مثلاً موعد قادم أو لم يكتب الطبيب روشتة)
        if (!$prescription) {
            return [
                'status_code' => 404,
                'response'    => [
                    'success' => false,
                    'message' => 'لا توجد روشتة طبية مسجلة لهذا الموعد حتى الآن.'
                ]
            ];
        }
        $prescription->makeHidden(['created_at', 'updated_at', 'appointment_id']);
        return [
            'status_code' => 200,
            'response'    => [
                'success' => true,
                'message' => 'تم جلب تفاصيل الروشتة بنجاح.',
                'data'    => $prescription
            ]
        ];
    }
public function createOrder(array $data)
{
    $user = Auth::user();

    // 1. جلب بيانات الطبيب الحالي
    $doctor = Doctor::where('user_id', $user->id)->first();
    if (!$doctor) {
        throw ValidationException::withMessages([
            'doctor' => 'الحساب الحالي غير مسجل كطبيب.',
        ]);
    }

    // 2. التحقق الأمني: هل الموعد يخص هذا الطبيب فعلاً؟
    // افترضت هنا أن جدول المواعيد يحتوي على حقل doctor_id
    $appointmentExists = Appointment::where('id', $data['appointment_id'])
                                    ->where('doctor_id', $doctor->id)
                                    ->exists();

    if (!$appointmentExists) {
        throw ValidationException::withMessages([
            'appointment_id' => 'لا يمكنك طلب تحليل لمريض غير مسجل في مواعيدك الشخصية.',
        ]);
    }

    // استخدام Transaction لضمان حفظ الطلب والتحاليل معاً
    return DB::transaction(function () use ($data) {

        // 3. إنشاء الطلب الرئيسي
        $labOrder = LabOrder::create([
            'appointment_id' => $data['appointment_id'],
            'doctor_notes'   => $data['doctor_notes'] ?? null,
            'overall_status' => 'pending',
        ]);

        // 4. تحضير مصفوفة التحاليل للـ Insert
        $testsData = [];
        $now = now(); // تعريف الوقت بره اللوب أفضل للأداء

        foreach ($data['tests'] as $testName) {
            $testsData[] = [
                'lab_order_id' => $labOrder->id,
                'test_name'    => $testName,
                'status'       => 'pending',
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        // 5. حفظ كل التحاليل دفعة واحدة
        LabOrderTest::insert($testsData);

        // إرجاع الطلب مع تفاصيله
        return $labOrder->load('tests');
    });
}
}
