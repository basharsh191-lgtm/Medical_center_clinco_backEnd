<?php
namespace App\Services;

use App\Models\HomeVisits;
use App\Models\Patient;

class HomeVisitService
{
    public function createPatientRequest(Patient $patient, array $data): array
    {
        try {
            // دمج رقم المريض مع البيانات
            $data['patient_id'] = $patient->id;

            // سيأخذ حالة 'pending' تلقائياً بناءً على الـ Migration
            // الـ doctor_id والـ receptionist_id سيكونان null
            $homeVisit = HomeVisits::create($data);

            return [
                'status_code' => 201,
                'response' => [
                    'success' => true,
                    'message' => 'تم إرسال طلب الرعاية المنزلية بنجاح، بانتظار موافقة الاستقبال.',
                    'data'    => $homeVisit
                ]
            ];

        } catch (\Exception $e) {
            \Log::error('Error creating home visit request: ' . $e->getMessage());

            return [
                'status_code' => 500,
                'response' => [
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إرسال الطلب، يرجى المحاولة لاحقاً.'
                ]
            ];
        }
    }
}
