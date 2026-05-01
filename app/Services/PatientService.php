<?php

namespace App\Services;

use App\Models\patient;
use Illuminate\Support\Facades\Auth;

class PatientService{
public function createPatient(array $data)
{
    // الحصول على ID المستخدم المسجل حالياً
    $userId = Auth::user()->id;
    $exists = Patient::where('user_id', $userId)->exists();

    if ($exists) {
        return response()->json([
            'message' => 'هذا المستخدم لديه حساب مريض بالفعل.'
        ], 422);
    }
    $patient = Patient::create([
        'user_id'          => $userId, // نضع القيمة مباشرة هنا
        'birth_date'       => $data['birth_date'],
        'blood_type'       => $data['blood_type'],
        'allergies'        => $data['allergies'] ?? null,
        'hereditary'       => $data['hereditary'] ?? null,
        'chronic_diseases' => $data['chronic_diseases'] ?? null,
        'gender'           => $data['gender'],
        'address'          => $data['address'],
    ]);

    return $patient->load('user');
}
}
