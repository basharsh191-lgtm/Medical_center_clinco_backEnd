<?php

namespace App\Http\Controllers;

use App\Http\Requests\DoctorRequest;
use App\Http\Requests\ReceptionRequest;
use App\Services\StaffService;

class StaffController extends Controller
{
protected $staffService;
public function __construct(StaffService $staffService)
    {
        $this->staffService = $staffService;
    }
public function storeReception(ReceptionRequest $request)
{
    $reception = $this->staffService->createStaff(
        $request->validated(),
        'reception'
    );

    return response()->json([
        'message' => 'Reception created successfully',
        'data' => $reception
    ], 201);
}
public function storeDoctor(DoctorRequest $request)
{
    $doctor = $this->staffService->createStaff(
        $request->validated(),
        'doctor'
    );

    return response()->json([
        'message' => 'Doctor created successfully',
        'data' => $doctor
    ], 201);
}

    }

