<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
    $carbonDate = Carbon::parse($date);
    $dayNameEn = $carbonDate->format('l');
    $dayNameAr = $this->translateDayToArabic($dayNameEn);

    $schedule = DoctorSchedule::where('doctor_id', $doctorId)
        ->where('day', $dayNameAr)
        ->where('is_active', true)
        ->first();

    if (!$schedule) {
        return [];
    }

    $bookedAppointments = Appointment::where('doctor_id', $doctorId)
        ->where('appointment_date', $date)
        ->whereIn('status', ['scheduled', 'confirmed', 'arrived', 'no_show'])
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

    // تعديل هنا: نعيد نوع الجدول والـ slots معاً
    return [
        'schedule_type' => $schedule->schedule_type,
        'slots'         => $slots
    ];
}
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
    public function isSlotAvailable(int $doctorId, string $date, string $startTime, string $endTime)
    {
        $availableSlots = $this->generateAvailableSlots($doctorId, $date);
        if (empty($availableSlots)) {
            return false;
        }

        foreach ($availableSlots as $slot) {
            if (
                $slot['start_time'] === Carbon::parse($startTime)->format('H:i') &&
                $slot['end_time'] === Carbon::parse($endTime)->format('H:i')
            ) {
                return $slot['is_available'];
            }
        }

        return false;
    }
    public function updateAppointment(Appointment $appointment, array $data)
    {
        if ($appointment->patient_id !== Auth::id()) {
            return [
                'success' => false,
                'message' => 'غير مصرح لك بتعديل هذا الموعد، هذا الموعد لا يخص حسابك.'
            ];
        }
        // 1 منع التعديل إذا كانت حالة الموعد الحالية لا تسمح
        if (in_array($appointment->status, ['completed', 'cancelled', 'arrived', 'no_show'])) {
            return [
                'success' => false,
                'message' => 'لا يمكنك تعديل موعد انتهى، رُفض، أو تم إلغاؤه بالفعل.'
            ];
        }
        $isAvailable = $this->isSlotAvailable(
            $appointment->doctor_id,
            $data['appointment_date'],
            $data['start_time'],
            $data['end_time']
        );
        if (!$isAvailable) {
            return [
                'success' => false,
                'message' => 'عذراً، الوقت الجديد المختار غير متاح أو محجوز.'
            ];
        }
        $appointment->update($data);
        return [
            'success' => true,
            'data' => $appointment
        ];
    }
    public function cancelAppointment(Appointment $appointment): array
    {
        if ($appointment->patient_id !== Auth::id()) {
            return [
                'status_code' => 403,
                'response' => [
                    'success' => false,
                    'message' => 'غير مصرح لك بإلغاء هذا الموعد، هذا الموعد لا يخص حسابك.'
                ]
            ];
        }
        if (in_array($appointment->status, ['completed', 'cancelled', 'arrived', 'no_show'])) {
            return [
                'status_code' => 422,
                'response' => [
                    'success' => false,
                    'message' => 'لا يمكنك إلغاء موعد انتهى، رُفض، أو تم إلغاؤه بالفعل.'
                ]
            ];
        }
        $appointment->update(['status' => 'cancelled']);
        return [
            'status_code' => 200,
            'response' => [
                'success' => true,
                'message' => 'تم إلغاء الموعد بنجاح.',
                'data'    => $appointment
            ]
        ];
    }
}
