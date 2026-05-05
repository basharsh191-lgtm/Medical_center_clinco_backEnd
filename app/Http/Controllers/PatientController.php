<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientRequest;
use App\Models\patient;
use App\Services\PatientService;
use Illuminate\Http\Request;

class PatientController extends Controller
{
protected $PatientService;

    public function __construct(PatientService $PatientService)
    {
        $this->PatientService = $PatientService;
    }
    public function storePatient(PatientRequest $request)
    {
    $validated=$request->validated();
    $patient=$this->PatientService->createPatient($validated);
        return response()->json([
                'success' => true,
                'data' => $patient
            ], 201);
    }
    public function showPatient($id)
    {
        return $this->PatientService->getPatientById($id);
    }
}
