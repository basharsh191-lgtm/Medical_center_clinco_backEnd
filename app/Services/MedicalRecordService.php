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
    public function getPrescription($id)
    {
        return Prescription::with('items')->findOrFail($id);
    }
    public function updatePrescription($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $prescription = Prescription::findOrFail($id);

            // تحديث التعليمات
            $prescription->update(['instructions' => $data['instructions'] ?? $prescription->instructions]);

            // تحديث الأدوية: الطريقة الأسهل هي حذف القديم وإضافة الجديد
            $prescription->items()->delete();
            $prescription->items()->createMany($data['items']);

            return $prescription->load('items');
        });
    }
    public function deletePrescription($id)
    {
        return DB::transaction(function () use ($id) {
            $prescription = Prescription::findOrFail($id);
            return $prescription->delete();
        });
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
    public function updateOrder($id, array $data)
    {
        $order = LabOrder::find($id);

        // 1. منع التعديل إذا كان الطلب غير موجود أو مكتمل
        if (!$order || $order->overall_status === 'completed') { // تأكد من اسم الحقل: overall_status أو status
            return null;
        }

        // 2. تحديث الحقول المسموح بها فقط (لتجنب الخطأ)
        // نستخدم only لنستثني حقل 'tests' من التحديث المباشر للجدول
        $order->update(collect($data)->except(['tests'])->toArray());

        // 3. إذا كان التعديل يشمل قائمة التحاليل
        if (isset($data['tests']) && is_array($data['tests'])) {

            // حذف التحاليل القديمة
            $order->tests()->delete();

            // إضافة التحاليل الجديدة
            $newTests = [];
            $now = now();
            foreach ($data['tests'] as $testName) {
                $newTests[] = [
                    'lab_order_id' => $order->id,
                    'test_name'    => $testName,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }

            // حفظ التحاليل الجديدة دفعة واحدة (Bulk Insert)
            LabOrderTest::insert($newTests);
        }

        return $order->load('tests');
    }
    public function getPatientHistory(int $patientId, int $specializationId)
    {
        return MedicalRecord::where('patient_id', $patientId)
            ->whereHas('doctor', function ($query) use ($specializationId) {
                // فلترة السجلات لتعود فقط للأطباء الذين ينتمون لنفس الاختصاص (أو العيادة)
                $query->where('specialization_id', $specializationId);
                // ملاحظة: إذا كان الربط في قاعدة بياناتك يعتمد على العيادة،
                // يمكنك استبدال specialization_id بـ clinic_id
            })
            ->with(['doctor.user', 'appointment']) // جلب البيانات المرتبطة
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function getPatientAllergies(int $patientId)
    {
        $patient = Patient::select('id', 'blood_type', 'allergies', 'chronic_diseases','hereditary')
            ->findOrFail($patientId);
        return $patient;
    }
    public function updatePatientAllergies(int $patientId, array $data)
    {
        $patient = Patient::findOrFail($patientId);
        $patient->update($data);
        return $patient->only(['id', 'blood_type', 'allergies', 'chronic_diseases', 'hereditary']);
    }
}
