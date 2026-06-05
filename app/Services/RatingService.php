<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Rating;
use App\Models\Appointment;

class RatingService
{
    public function store(array $data, int $doctorId, int $patientId)
    {
        $doctor = Doctor::findOrFail($doctorId);

        $hasCompletedSession = Appointment::where('patient_id', $patientId)
            ->where('doctor_id', $doctorId)
            ->where('status', 'completed')
            ->exists();

        if (!$hasCompletedSession) {
            throw new \Exception(
                'عذراً، لا يمكنك تقييم هذا الطبيب إلا بعد إتمام جلسة علاجية معه.',
                403
            );
        }

        $alreadyRated = Rating::where('patient_id', $patientId)
            ->where('rateable_id', $doctorId)
            ->where('rateable_type', Doctor::class)
            ->exists();

        if ($alreadyRated) {
            throw new \Exception(
                'لقد قمت بتقييم هذا الطبيب مسبقاً.',
                409
            );
        }

        return $doctor->ratings()->create([
            'patient_id' => $patientId,
            'stars'      => $data['stars'],
            'comment'    => $data['comment'] ?? null,
        ]);
    }
}
