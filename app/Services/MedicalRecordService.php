<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\HomeVisit;
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
    public function storePrescriptionHomeVisit(HomeVisit $homevisit, array $data): array
    {
        try {
            DB::beginTransaction();

            // حل المشكلة: إذا كان الـ Route binding لم يقرأ الـ ID، نقوم بجلبه يدويًا للتأكد 100%
            // وإذا كان موجودًا، سيعمل بشكل طبيعي.
            if (!$homevisit->exists) {
                // ملاحظة: تأكد أنك تقوم بتمرير متغير يحمل ID من الـ Controller
                // أو قم بالبحث عنه باستخدام الرقم التعريفي القادم من الطلب
                return [
                    'status_code' => 404,
                    'response' => ['success' => false, 'message' => 'الزيارة المنزلية غير موجودة.']
                ];
            }

            // 1. إنشاء رأس الوصفة الطبية (Prescription) عبر علاقة الزيارة مباشرة
            // هذه الطريقة تجعل Laravel يملأ حقل home_visit_id تلقائيًا بنسبة 100% بدون أي أخطاء
            $prescription = $homevisit->prescriptions()->create([
                'instructions' => $data['instructions'] ?? null,
            ]);

            // 2. إدخال عناصر الأدوية دفعة واحدة
            if (!empty($data['items'])) {
                $prescription->items()->createMany($data['items']);
            }

            // 3. تحويل حالة الزيارة المنزلية تلقائياً إلى مكتمل
            $homevisit->update(['status' => 'completed']);

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
            DB::rollBack();

            return [
                'status_code' => 500,
                'response' => [
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حفظ الروشتة، يرجى المحاولة لاحقاً.',
                    'error'   => $e->getMessage()
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
    public function createHomeOrder(array $data)
{
    return DB::transaction(function () use ($data) {
        $labOrder = LabOrder::create([
            'home_visit_id'  => $data['home_visit_id'],
            'doctor_notes'   => $data['doctor_notes'] ?? null,
            'overall_status' => 'pending',
        ]);

        if (!empty($data['tests'])) {
            foreach ($data['tests'] as $testName) {
                $labOrder->tests()->create([
                    'test_name' => $testName
                ]);
            }
        }

        return $labOrder->load('tests');
    });
    }
    public function updateOrder(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            // البحث عن الطلب المتاح للتعديل فقط (الحالة pending)
            $labOrder = LabOrder::where('id', $id)
                ->where('overall_status', 'pending')
                ->first();

            if (!$labOrder) {
                return null;
            }

            // 1. تحديث ملاحظات الطبيب إذا وجدت
            if (array_key_exists('doctor_notes', $data)) {
                $labOrder->update([
                    'doctor_notes' => $data['doctor_notes']
                ]);
            }

            // 2. إعادة إسناد التحاليل في حال تم تغيير القائمة
            if (isset($data['tests']) && is_array($data['tests'])) {
                // حذف التحاليل القديمة والمرتبطة بالطلب
                $labOrder->tests()->delete();

                // إضافة التحاليل الجديدة
                foreach ($data['tests'] as $testName) {
                    $labOrder->tests()->create([
                        'test_name' => $testName
                    ]);
                }
            }

            // إرجاع الطلب مع التحاليل المحدثة
            return $labOrder->load('tests');
        });
    }
    public function updatePrescriptionHomeVisit(HomeVisit $homevisit, array $data)
    {
        return DB::transaction(function () use ($homevisit, $data) {
            $prescription = $homevisit->prescriptions->first();

            if (!$prescription) {
                return [
                    'status_code' => 404,
                    'response'    => [
                        'success' => false,
                        'message' => 'لم يتم العثور على روشتة مرتبطة بهذه الزيارة للتعديل.'
                    ]
                ];
            }

            // 1. تحديث التعليمات إن وجدت
            if (array_key_exists('instructions', $data)) {
                $prescription->update([
                    'instructions' => $data['instructions']
                ]);
            }

            // 2. تحديث قائمة الأدوية (حذف القديم وإضافة الجديد)
            if (isset($data['items']) && is_array($data['items'])) {
                $prescription->items()->delete();

                foreach ($data['items'] as $item) {
                    $prescription->items()->create([
                        'medicine_name' => $item['medicine_name'],
                        'dosage'        => $item['dosage'],
                        'frequency'     => $item['frequency'],
                        'duration'      => $item['duration'],
                    ]);
                }
            }

            return [
                'status_code' => 200,
                'response'    => [
                    'success' => true,
                    'message' => 'تم تحديث الروشتة بنجاح',
                    'data'    => $prescription->load('items')
                ]
            ];
        });
    }
    public function deletePrescriptionHomeVisit(HomeVisit $homevisit)
    {
        return DB::transaction(function () use ($homevisit) {
            $prescription = $homevisit->prescriptions->first();

            if (!$prescription) {
                return [
                    'status_code' => 404,
                    'response'    => [
                        'success' => false,
                        'message' => 'لا توجد روشتة مرتبطة بهذه الزيارة لحذفها.'
                    ]
                ];
            }

            // حذف الروشتة والأدوية المرتبطة بها (إذا كان Cascade أو يحذف يدوياً)
            $prescription->items()->delete();
            $prescription->delete();

            return [
                'status_code' => 200,
                'response'    => [
                    'success' => true,
                    'message' => 'تم حذف الروشتة بنجاح'
                ]
            ];
        });
    }
}
