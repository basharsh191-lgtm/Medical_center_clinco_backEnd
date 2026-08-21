<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Appointment;
use Carbon\Carbon;
use Exception;

class ReceptionService
{
    /**
     * تسجيل وصول المريض بناءً على الـ QR والعيادة الخاصة بموظف الاستقبال.
     *
     * @param string $qrToken
     * @param int $clinicId
     * @return Appointment
     * @throws Exception
     */
    public function checkInByQr(string $qrToken, int $clinicId): Appointment
    {
        // 1. جلب المريض
        $patient = Patient::where('qr_token', $qrToken)->first();

        if (!$patient) {
            throw new Exception('المريض غير موجود.', 404);
        }

        // 2. البحث عن موعد اليوم في نفس العيادة
        $appointment = Appointment::where('patient_id', $patient->id)
            ->where('clinic_id', $clinicId)
            ->where('status', 'scheduled')
            ->whereDate('appointment_date', Carbon::today())
            ->with(['doctor', 'patient.user']) // شحن العلاقات المطلوبة للاستجابة لاحقاً
            ->first();

        // 3. التحقق من وجود الموعد
        if (!$appointment) {
            // الموعد قد يكون موجوداً اليوم لكن بحالة أخرى (arrived/completed/cancelled).
            // بدون التمييز تظهر رسالة "لا يوجد موعد" فيظن موظف الاستقبال أن الحجز مفقود،
            // بينما السبب الشائع هو أن الوصول سُجّل مسبقاً.
            $todayAppointment = Appointment::where('patient_id', $patient->id)
                ->where('clinic_id', $clinicId)
                ->whereDate('appointment_date', Carbon::today())
                ->first();

            if ($todayAppointment && $todayAppointment->status === 'arrived') {
                throw new Exception('تم تسجيل وصول هذا المريض مسبقاً.', 409);
            }

            if ($todayAppointment) {
                throw new Exception(
                    "لا يمكن تسجيل الوصول، حالة موعد اليوم لهذا المريض هي ({$todayAppointment->status}).",
                    409
                );
            }

            throw new Exception('لا يوجد موعد مجدول لهذا المريض اليوم في هذه العيادة.', 404);
        }

        // 4. تحديث حالة الموعد إلى "وصل"
        $appointment->update([
            'status' => 'arrived'
        ]);

        return $appointment;
    }
}
