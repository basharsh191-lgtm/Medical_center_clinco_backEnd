<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\patient;
use Illuminate\Support\Facades\Auth;

class PatientService{
public function createPatient(array $data)
{
    $userId = Auth::id();

    if (Patient::where('user_id', $userId)->exists()) {
        return null;
    }

    $patient = Patient::create([
        'user_id'          => $userId,
        'birth_date'       => $data['birth_date'],
        'blood_type'       => $data['blood_type'],
        'allergies'        => $data['allergies'] ?? null,
        'hereditary'       => $data['hereditary'] ?? null,
        'chronic_diseases' => $data['chronic_diseases'] ?? null,
        'gender'           => $data['gender'],
        'address'          => $data['address'],
        'taller'           => $data['taller'] ?? null,
        'weight'           => $data['weight'] ?? null,
    ]);

    return $patient->load('user');
}
public function getMyProfile()
{
    $user = Auth::user();

    $patient = Patient::where('user_id', $user->id)->with('user')->first();

    if(!$patient) {
        return response()->json(['message' => 'Profile not found'], 404);
    }

$patient->image_url = $patient->image ? asset('storage/' . $patient->image) : null;

    return response()->json($patient, 200);
}
public function getAllAppointments()
    {
        $user = Auth::user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return [
                'status_code' => 404,
                'response'    => [
                    'success' => false,
                    'message' => 'لم يتم العثور على ملف مريض لهذا الحساب.'
                ]
            ];
        }
        $appointments = Appointment::with([
            'doctor' => function($query) {
                $query->select('id', 'user_id', 'image')
                    ->with('user:id,name');
            },
            'clinic:id,clinic_name'
        ])
        ->where('patient_id', $patient->id)
        ->orderBy('appointment_date', 'desc')
        ->orderBy('start_time', 'desc')
        ->get();

        //$upcoming (للمواعيد القادمة)
        //$past (للمواعيد السابقة)
        //$cancelled (للمواعيد الملغية)

        $upcoming  = [];
        $past      = [];
        $cancelled = [];

        $currentDate = now()->toDateString();
        $currentTime = now()->toTimeString();

// فرز المواعيد
        foreach ($appointments as $appointment) {

            if ($appointment->status === 'cancelled') {
                $cancelled[] = $appointment;
            }
            elseif (in_array($appointment->status, ['completed', 'no_show', 'arrived'])) {
                $past[] = $appointment;
            }
            elseif ($appointment->status === 'scheduled') {
                if ($appointment->appointment_date >= $currentDate) {
                    $upcoming[] = $appointment;
                }
                else {
                    $past[] = $appointment;
                }
            }
        }

        return [
            'status_code' => 200,
            'response'    => [
                'success' => true,
                'message' => 'تم جلب المواعيد بنجاح.',
                'data'    => [
                    'upcoming'  => $upcoming,
                    'past'      => $past,
                    'cancelled' => $cancelled,
                ]
            ]
        ];
}
}
