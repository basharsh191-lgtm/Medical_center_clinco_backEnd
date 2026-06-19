<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Doctor;
use Illuminate\Http\Request;

class ClincController extends Controller
{
    public function showClinic($id)
    {
        $clinic = Clinic::with('doctors')->findOrFail($id);
        return response()->json([
            'clinic' => $clinic,
        ], 200);
    }
public function showClinicAll()
{
    $clinics = Clinic::all();
    return response()->json([
        'clinics' => $clinics,
    ], 200);
}
public function showDoctor($id)
{
    $doctor = Doctor::with(['user', 'speciality'])->findOrFail($id);
    $doctor->ratings_avg_stars = $doctor->averageRating();

    return response()->json($doctor, 200);
}
public function getClinicsWithDoctors()
{
    $clinics = Clinic::with(['doctors' => function($query) {

        // 1. جلب الأعمدة المطلوبة من جدول الأطباء
        $query->select('id', 'clinic_id', 'user_id', 'specialization_id', 'experience_years', 'image')

              // 2. جلب بيانات المستخدم المرتبط بالطبيب
              ->with(['user' => function($userQuery) {
                  $userQuery->select('id', 'name', 'last_name');
              }])

              // 3. الحل هنا: تمرير اسم العمود الحقيقي 'stars' وليس اسم الدالة
              ->withAvg('ratings', 'stars');

    }])->get();

    return response()->json([
        'success' => true,
        'data' => $clinics
    ], 200);
}
}
