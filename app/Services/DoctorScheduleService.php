<?php

namespace App\Services;

use App\Models\DoctorSchedule;
use App\Models\Doctor;
use App\Models\Reception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ValidationException;

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

    $receptionist = Auth::user()->reception;

    if (!$receptionist) {
        // نطلق Exception بدلاً من إرجاع ريسبونس مباشر
        throw new \Exception("عذراً، هذا الحساب ليس لديه صلاحيات موظف استقبال.", 403);
    }

    $receptionistClinicId = $receptionist->clinic_id;

    $doctor = Doctor::findOrFail($doctorId);
    $doctorClinicId = $doctor->clinic_id;

    if ($receptionistClinicId !== $doctorClinicId) {
        throw new \Exception('غير مسموح لك! هذا الطبيب لا ينتمي للعيادة الخاصة بك', 403);
    }

    $hasConflict = DoctorSchedule::where('day', $day)
        ->where('is_active', true)
        ->where('doctor_id', '!=', $doctorId)
        ->whereHas('doctor', function ($query) use ($receptionistClinicId) {
            $query->where('clinic_id', $receptionistClinicId);
        })
        ->where(function ($query) use ($startTime, $endTime) {
            $query->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
        })
        ->exists();

    if ($hasConflict) {
        throw new \Exception("عذراً، يوجد طبيب آخر في عيادتكم لديه دوام يتعارض مع هذا الوقت في يوم {$day}.", 422);
    }

    return DoctorSchedule::updateOrCreate(
        [
            'doctor_id' => $doctorId,
            'day' => $day,
        ],
        [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'appointment_duration' => $data['appointment_duration'] ?? 15,
            'is_active' => $data['is_active'] ?? true,
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
