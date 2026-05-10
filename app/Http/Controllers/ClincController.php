<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Doctor;
use Illuminate\Http\Request;

class ClincController extends Controller
{
    public function showClinic($id)
    {
        $clinic = Clinic::with('doctor')->findOrFail($id);
        return response()->json([
            'clinic' => $clinic,
        ], 200);
    }
    public function showDoctor($id)
    {
        $doctor=Doctor::with('user')->findOrFail($id);
        return response()->json($doctor, 200);
    }
}
