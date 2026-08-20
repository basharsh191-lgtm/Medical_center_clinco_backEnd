<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\HomeVisit;
use App\Models\Patient;
use App\Models\Reception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminController extends Controller
{

    public function forceDelete($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            return response()->json([
                'status'  => false,
                'message' => 'You cannot force delete your own account.'
            ], 403);
        }
        $user->delete();
        return response()->json([
            'status'  => true,
            'message' => 'User permanently deleted from database.'
        ]);
    }
    public function getPatients()
        {
            $totalPatients = Patient::count();
            $patients = Patient::with(['user' => function ($query) {
                $query->select('id', 'name', 'last_name', 'email', 'phone', 'is_verified');
            }])
            ->latest()
            ->paginate(15);

            return response()->json([
                'status' => true,
                'message' => 'Patients retrieved successfully.',
                'total_count' => $totalPatients,
                'data' => $patients
            ]);
    }
    public function getDoctors()
    {
        $totalDoctors = Doctor::count();
        $doctors = Doctor::with([
            'user' => function ($query) {
                $query->select('id', 'name', 'last_name', 'email', 'phone', 'is_verified');
            },
            'speciality',
            'clinic'
        ])
        ->withAvg('ratings', 'stars')
        ->latest()
        ->paginate(15);

        return response()->json([
            'status' => true,
            'message' => 'Doctors retrieved successfully.',
            'total_count' => $totalDoctors,
            'data' => $doctors
        ]);
    }
    public function getReceptionists()
    {
    $totalReceptionists = Reception::count();
    $receptionists = Reception::with([
        'user' => function ($query) {
            $query->select('id', 'name', 'last_name', 'email', 'phone', 'is_verified');
        },
        'clinic'
    ])
    ->latest()
    ->paginate(15);
    return response()->json([
        'status' => true,
        'message' => 'Receptionists retrieved successfully.',
        'total_count' => $totalReceptionists,
        'data' => $receptionists
    ]);
    }
    public function getClinicAppointments(Request $request, $clinicId)
    {
        $clinic = Clinic::findOrFail($clinicId);
        $query = $clinic->appointments()->with([
            'patient.user:id,name,last_name,email,phone',
            'doctor.user:id,name,last_name,email,phone'
        ]);
        if ($request->has('status') && in_array($request->status, ['scheduled', 'completed', 'no_show'])) {
            $query->where('status', $request->status);
        }
        $appointments = $query->latest()->paginate(15);
        $counts = [
            'total'     => $clinic->appointments()->count(),
            'scheduled' => $clinic->appointments()->where('status', 'scheduled')->count(),
            'completed' => $clinic->appointments()->where('status', 'completed')->count(),
            'no_show'   => $clinic->appointments()->where('status', 'no_show')->count(),
        ];
        return response()->json([
            'status'  => true,
            'message' => 'Clinic appointments retrieved successfully.',
            'clinic'  => [
                'id'   => $clinic->id,
                'clinic_name' => $clinic->clinic_name,
            ],
            'counts' => $counts,
            'data'   => $appointments
     ]);
    }
    public function getHomeVisits(Request $request)
    {
        $totalHomeVisits = HomeVisit::count();
        $query = HomeVisit::with([
            'patient.user:id,name,last_name,email,phone',
            'doctor.user:id,name,last_name,email,phone',
            'doctor.speciality',
            'doctor.clinic'
        ]);
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        $homeVisits = $query->latest()->paginate(15);
        return response()->json([
            'status'            => true,
            'message'           => 'Home visits retrieved successfully.',
            'total_home_visits' => $totalHomeVisits,
            'data'              => $homeVisits
        ]);
    }

}

