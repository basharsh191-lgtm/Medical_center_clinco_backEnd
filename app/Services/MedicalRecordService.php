<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\HomeVisit;
use App\Models\LabOrder;
use App\Models\LabOrderTest;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;

class MedicalRecordService
{
    public function createRecord(array $data): MedicalRecord
    {
        return DB::transaction(function () use ($data) {
            $record = MedicalRecord::create($data);

            if (!empty($data['appointment_id'])) {
                $appointment = Appointment::find($data['appointment_id']);
                if ($appointment) {
                    $appointment->update(['status' => 'completed']);
                }
            }

            return $record;
        });
    }
    public function getPatientHistory(int $patientId, int $specializationId)
    {
        return MedicalRecord::where('patient_id', $patientId)
            ->whereHas('doctor', function ($query) use ($specializationId) {
                $query->where('specialization_id', $specializationId);
            })
            ->with([
                'doctor.user',
                'appointment',
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function getPatientAllergies(int $patientId)
    {
        return Patient::select('id', 'blood_type', 'allergies', 'chronic_diseases', 'hereditary')
            ->findOrFail($patientId);
    }
    public function updatePatientAllergies(int $patientId, array $data)
    {
        $patient = Patient::findOrFail($patientId);
        $patient->update($data);
        return $patient->only(['id', 'blood_type', 'allergies', 'chronic_diseases', 'hereditary']);
    }
    public function storePrescription(Appointment $appointment, array $data): array
    {
        try {
            DB::beginTransaction();

            $prescription = Prescription::create([
                'appointment_id' => $appointment->id,
                'instructions'   => $data['instructions'] ?? null,
            ]);

            if (!empty($data['items'])) {
                $prescription->items()->createMany($data['items']);
            }

            $appointment->update(['status' => 'completed']);

            DB::commit();

            return [
                'status_code' => 201,
                'response' => [
                    'success' => true,
                    'message' => 'تم تسجيل الروشتة الطبية وإنهاء الجلسة بنجاح.',
                    'data'    => ['prescription_id' => $prescription->id]
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
    public function getPrescription($id)
    {
        return Prescription::with('items')->findOrFail($id);
    }
    public function updatePrescription($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $prescription = Prescription::findOrFail($id);

            $prescription->update(['instructions' => $data['instructions'] ?? $prescription->instructions]);

            if (isset($data['items']) && is_array($data['items'])) {
                $prescription->items()->delete();
                $prescription->items()->createMany($data['items']);
            }

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
    public function storePrescriptionHomeVisit(HomeVisit $homevisit, array $data): array
    {
        try {
            DB::beginTransaction();

            if (!$homevisit->exists) {
                return [
                    'status_code' => 404,
                    'response' => ['success' => false, 'message' => 'الزيارة المنزلية غير موجودة.']
                ];
            }

            $prescription = $homevisit->prescriptions()->create([
                'instructions' => $data['instructions'] ?? null,
            ]);

            if (!empty($data['items'])) {
                $prescription->items()->createMany($data['items']);
            }

            $homevisit->update(['status' => 'completed']);

            DB::commit();

            return [
                'status_code' => 201,
                'response' => [
                    'success' => true,
                    'message' => 'تم تسجيل الروشتة الطبية للزيارة المنزلية بنجاح.',
                    'data'    => ['prescription_id' => $prescription->id]
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

            if (array_key_exists('instructions', $data)) {
                $prescription->update(['instructions' => $data['instructions']]);
            }

            if (isset($data['items']) && is_array($data['items'])) {
                $prescription->items()->delete();
                $prescription->items()->createMany($data['items']);
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
    public function createOrder(array $data)
    {
        $user = Auth::user();

<<<<<<< Updated upstream
    // ==========================================
    // 4. طلبات التحاليل (Lab Orders) - عيادة وزيارات
    // ==========================================

public function createOrder(array $data)
{
    $user = Auth::user();

    $doctor = Doctor::where('user_id', $user->id)->first();
    if (!$doctor) {
        throw ValidationException::withMessages([
            'doctor' => 'الحساب الحالي غير مسجل كطبيب.',
        ]);
    }

    $appointmentExists = Appointment::where('id', $data['appointment_id'])
                                     ->where('doctor_id', $doctor->id)
                                     ->exists();

    if (!$appointmentExists) {
        throw ValidationException::withMessages([
            'appointment_id' => 'لا يمكنك طلب تحليل لمريض غير مسجل في مواعيدك الشخصية.',
        ]);
    }

    return DB::transaction(function () use ($data) {
        $labOrder = LabOrder::create([
            'appointment_id' => $data['appointment_id'],
            'doctor_notes'   => $data['doctor_notes'] ?? null,
            'overall_status' => 'pending',
        ]);

        if (!empty($data['tests'])) {
            $testsData = array_map(function ($testName) {
                return [
                    'test_name' => $testName,
                ];
            }, $data['tests']);

            // استخدام createMany يضمن المرور عبر Eloquent وحل مشاكل Strict Mode
            $labOrder->tests()->createMany($testsData);
=======
        $doctor = Doctor::where('user_id', $user->id)->first();
        if (!$doctor) {
            throw ValidationException::withMessages([
                'doctor' => 'الحساب الحالي غير مسجل كطبيب.',
            ]);
>>>>>>> Stashed changes
        }

        $appointmentExists = Appointment::where('id', $data['appointment_id'])
            ->where('doctor_id', $doctor->id)
            ->exists();

        if (!$appointmentExists) {
            throw ValidationException::withMessages([
                'appointment_id' => 'لا يمكنك طلب تحليل لمريض غير مسجل في مواعيدك الشخصية.',
            ]);
        }

        return DB::transaction(function () use ($data) {
            $labOrder = LabOrder::create([
                'appointment_id' => $data['appointment_id'],
                'doctor_notes'   => $data['doctor_notes'] ?? null,
                'overall_status' => 'pending',
            ]);

            if (!empty($data['tests'])) {
                $testsData = array_map(function ($testName) {
                    return [
                        'test_name' => $testName,
                        'status'    => 'pending',
                    ];
                }, $data['tests']);

                // استخدام createMany يضمن المرور عبر Eloquent وحل مشاكل Strict Mode
                $labOrder->tests()->createMany($testsData);
            }

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
            $labOrder = LabOrder::where('id', $id)
                ->where('overall_status', 'pending')
                ->first();

            if (!$labOrder) {
                return null;
            }

            if (array_key_exists('doctor_notes', $data)) {
                $labOrder->update(['doctor_notes' => $data['doctor_notes']]);
            }

            if (isset($data['tests']) && is_array($data['tests'])) {
                $labOrder->tests()->delete();

                foreach ($data['tests'] as $testName) {
                    $labOrder->tests()->create([
                        'test_name' => $testName
                    ]);
                }
            }

            return $labOrder->load('tests');
        });
    }
}
