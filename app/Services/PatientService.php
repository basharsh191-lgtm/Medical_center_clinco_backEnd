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
            'clinic:id,clinic_name',
            'prescription.items'
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
public function updatePatient(Patient $patient, array $data)
{
        $patient->update($data);
        return $patient->refresh();
}
// جلب الأطباء المفضليين مع بياناتهم الأساسية وتخصصاتهم
public function getFavoriteDoctors(Patient $patient)
{
    return $patient->favoriteDoctors()
        ->with(['user:id,name', 'speciality:id,specialization_name'])
        ->get();
}
// عمل إضافة أو حذف تلقائي للطبيب من المفضلة
public function toggleDoctorFavorite(Patient $patient, \App\Models\Doctor $doctor): array
{
    try {
        $result = $patient->favoriteDoctors()->toggle($doctor->id);

        if (count($result['attached']) > 0) {
            $message = 'تم إضافة الطبيب إلى المفضلة بنجاح.';
            $isFavorite = true;
        } else {
            $message = 'تم إزالة الطبيب من المفضلة بنجاح.';
            $isFavorite = false;
        }

        return [
            'status_code' => 200,
            'response' => [
                'success'     => true,
                'message'     => $message,
                'is_favorite' => $isFavorite // مفيد جداً للفلاتر لتحديث شكل زر القلب فوراً
            ]
        ];

    } catch (\Exception $e) {
        return [
            'status_code' => 500,
            'response' => [
                'success' => false,
                'message' => 'حدث خطأ أثناء تعديل المفضلة.',
                'error'   => $e->getMessage()
            ]
        ];
    }
}
public function getNextAppointment(Patient $patient)
{
    $now = now();

    $nextAppointment = Appointment::with([
            // جلب بيانات الطبيب وتخصصه وعيادته لكي نعرضها في بطاقة التذكير
            'doctor.user:id,name',
            'doctor.speciality:id,specialization_name',
            'clinic:id,clinic_name'
        ])
        ->where('patient_id', $patient->id)
        ->where('status', 'scheduled') // فقط المواعيد المحجوزة
        ->where(function ($query) use ($now) {
            // إما أن يكون الموعد في الأيام القادمة
            $query->where('appointment_date', '>', $now->toDateString())
                  // أو أن يكون الموعد اليوم، ولكن وقته لم يأتِ بعد
                  ->orWhere(function ($q) use ($now) {
                      $q->where('appointment_date', '=', $now->toDateString())
                        ->where('start_time', '>=', $now->toTimeString());
                  });
        })
        // الترتيب تصاعدياً حسب التاريخ ثم الوقت لنجلب "الأقرب"
        ->orderBy('appointment_date', 'asc')
        ->orderBy('start_time', 'asc')
        ->first(); // جلب أول نتيجة فقط

    // إذا لم يكن لديه مواعيد قادمة
    if (!$nextAppointment) {
        return [
            'status_code' => 200,
            'response' => [
                'success' => true,
                'message' => 'لا يوجد لديك مواعيد قادمة.',
                'data'    => null
            ]
        ];
    }

    // إخفاء الحقول غير الضرورية للفرونت إند لتنظيف الـ JSON
    $nextAppointment->makeHidden(['created_at', 'updated_at']);

    return [
        'status_code' => 200,
        'response' => [
            'success' => true,
            'message' => 'تم جلب أقرب موعد بنجاح.',
            'data'    => $nextAppointment
        ]
    ];
}
}
