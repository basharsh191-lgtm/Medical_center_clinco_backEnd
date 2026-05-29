<?php

namespace App\Services;

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

}
