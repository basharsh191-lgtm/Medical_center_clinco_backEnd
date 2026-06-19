<?php

namespace App\Services;

use App\Models\DoctorSchedule;
use App\Models\Doctor;

use Illuminate\Support\Facades\Auth;


class DoctorScheduleService
{
    /**
     * إنشاء أو تحديث وقت دوام الطبيب بواسطة موظف الاستقبال
     */
public function createOrUpdateSchedule(array $data)
    {
        $doctorId = $data['doctor_id'];
        $day = $data['day'];
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];
        $scheduleType = $data['schedule_type'];

        $receptionist = Auth::user()->reception;

        if (!$receptionist) {
            throw new \Exception("عذراً، هذا الحساب ليس لديه صلاحيات موظف استقبال.", 403);
        }

        $receptionistClinicId = $receptionist->clinic_id;

        $doctor = Doctor::findOrFail($doctorId);
        $doctorClinicId = $doctor->clinic_id;

        if ($receptionistClinicId !== $doctorClinicId) {
            throw new \Exception('غير مسموح لك! هذا الطبيب لا ينتمي للعيادة الخاصة بك', 403);
        }

        // منطق التعارض: نتحقق من التعارض فقط إذا كان الطبيب سيستخدم مبنى العيادة (clinic أو both)
        if (in_array($scheduleType, ['clinic', 'both'])) {
            $hasConflict = DoctorSchedule::where('day', $day)
                ->where('is_active', true)
                ->where('doctor_id', '!=', $doctorId)
                ->whereIn('schedule_type', ['clinic', 'both']) // تجاهل أطباء الزيارات المنزلية من التعارض
                ->whereHas('doctor', function ($query) use ($receptionistClinicId) {
                    $query->where('clinic_id', $receptionistClinicId);
                })
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->exists();

            if ($hasConflict) {
                throw new \Exception("عذراً، يوجد طبيب آخر في عيادتكم يستخدم العيادة في هذا الوقت بيوم {$day}.", 422);
            }
        }

        // تحديد مدة الجلسة بناءً على نوع الدوام في حال لم يتم إرسالها في الـ Request
        $duration = $data['appointment_duration'] ?? match($scheduleType) {
            'home' => 60,
            'both' => 45,
            default => 25,
        };

        return DoctorSchedule::updateOrCreate(
            [
                'doctor_id' => $doctorId,
                'day'       => $day,
            ],
            [
                'start_time'           => $startTime,
                'end_time'             => $endTime,
                'appointment_duration' => $duration,
                'schedule_type'        => $scheduleType, // تخزين نوع الدوام
                'is_active'            => $data['is_active'] ?? true,
            ]
        );
    }
public function getClinicDoctors()
{
    $receptionist = Auth::user()->reception;

    if (!$receptionist) {
        throw new \Exception("عذراً، هذا الحساب ليس لديه صلاحيات موظف استقبال.");
    }
    $clinicId = $receptionist->clinic_id;
    return Doctor::where('clinic_id', $clinicId)
        ->with('user:id,name,last_name,email')
        ->get();
}
}
