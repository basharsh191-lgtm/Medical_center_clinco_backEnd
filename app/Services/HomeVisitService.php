<?php
namespace App\Services;

use App\Models\HomeVisits;
use App\Models\Patient;
use Exception;

class HomeVisitService
{
public function bookHomeVisit(Patient $patient, array $data): HomeVisits
{
    // 1. التحقق من أن هذا الوقت لم يُحجز مسبقاً بناءً على التاريخ والوقت
    $slotTaken = HomeVisits::where('doctor_id', $data['doctor_id'])
        ->where('visit_date', $data['visit_date'])
        ->whereIn('status', ['pending', 'assigned', 'on_the_way'])
        ->where(function ($query) use ($data) {
            // شرط التحقق من التداخل الزمني
            $query->where('start_time', '<', $data['end_time'])
                  ->where('end_time', '>', $data['start_time']);
        })
        ->exists();

    if ($slotTaken) {
        throw new Exception('عذراً، هذا الموعد تم حجزه للتو من قبل مريض آخر، يرجى اختيار وقت مختلف.');
    }

    try {
        // 2. دمج معرف المريض
        $data['patient_id'] = $patient->id;

        // 3. إنشاء الحجز
        return HomeVisits::create($data);

    } catch (Exception $e) {
        \Log::error('Error booking home visit: ' . $e->getMessage());
        throw new Exception('حدث خطأ داخلي أثناء تسجيل الحجز، يرجى المحاولة لاحقاً.');
    }
}
}
