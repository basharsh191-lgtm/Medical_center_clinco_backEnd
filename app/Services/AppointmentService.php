<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Carbon\Carbon;

class AppointmentService
{
    /**
     * توليد جميع الفترات الزمنية المتاحة والمحجوزة لطبيب في تاريخ معين
     *
     * @param int $doctorId
     * @param string $date (Format: Y-m-d)
     * @return array
     */
public function generateAvailableSlots(int $doctorId, string $date): array
{
    // 1. معرفة اسم اليوم باللغة العربية
    $carbonDate = Carbon::parse($date);
    $dayNameEn = $carbonDate->format('l');
    $dayNameAr = $this->translateDayToArabic($dayNameEn);

    // 2. جلب دوام الدكتور في هذا اليوم
    $schedule = DoctorSchedule::where('doctor_id', $doctorId)
        ->where('day', $dayNameAr)
        ->where('is_active', true)
        ->first();

    if (!$schedule) {
        return [];
    }

    // 3. التعديل هنا: جلب المواعيد بناءً على عمود الـ appointment_date الفعلي
    $bookedAppointments = Appointment::where('doctor_id', $doctorId)
        ->where('appointment_date', $date) // التعديل الفعلي للتاريخ 🎯
        ->whereIn('status', ['pending', 'confirmed', 'arrived'])
        ->get(['start_time', 'end_time']);

    $slots = [];

    // دمج التاريخ المختار مع وقت بداية ونهاية الدوام للمقارنة الزمنية
    $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
    $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
    $duration = $schedule->appointment_duration;

    while ($startTime->copy()->addMinutes($duration)->lte($endTime)) {
        $slotStart = $startTime->copy();
        $slotEnd = $startTime->copy()->addMinutes($duration);

        // فحص التداخل
        $isBooked = $bookedAppointments->contains(function ($appointment) use ($slotStart, $slotEnd, $date) {
            // ندمج تاريخ اليوم المرسل مع وقت الحجز المخزن بقاعدة البيانات لتصبح المقارنة دقيقة كـ DateTime
            $appointmentStart = Carbon::parse($date . ' ' . $appointment->start_time);
            $appointmentEnd = Carbon::parse($date . ' ' . $appointment->end_time);

            return $slotStart->lt($appointmentEnd) && $slotEnd->gt($appointmentStart);
        });

        $slots[] = [
            'start_time'   => $slotStart->format('H:i'),
            'end_time'     => $slotEnd->format('H:i'),
            'is_available' => !$isBooked,
        ];

        $startTime->addMinutes($duration);
    }

    return $slots;
}

    /**
     * دالة مساعدة لتحويل اسم اليوم من الإنكليزية إلى العربية لتتوافق مع الـ Migration
     */
    private function translateDayToArabic(string $dayNameEn): string
    {
        $days = [
            'Monday'    => 'الإثنين',
            'Tuesday'   => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday'  => 'الخميس',
            'Friday'    => 'الجمعة',
            'Saturday'  => 'السبت',
            'Sunday'    => 'الأحد',
        ];

        return $days[$dayNameEn] ?? '';
    }
}
