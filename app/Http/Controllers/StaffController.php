<?php

namespace App\Http\Controllers;

use App\Http\Requests\DoctorRequest;
use App\Http\Requests\ReceptionRequest;
use App\Services\StaffService;
use App\UploadFileTrait;

class StaffController extends Controller
{
protected $staffService;
use UploadFileTrait;

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
    $validatedData = $request->validated();
    if ($request->hasFile('image')) {
        $path = $this->upload($request->file('image'), 'doctor_picture', 'public');
        $validatedData['image'] = url('storage/' . $path);
    } else {
        $validatedData['image'] = null;
    }
    $doctor = $this->staffService->createStaff(
        $validatedData,
        'doctor'
    );
    return response()->json([
        'message' => 'Doctor created successfully',
        'data' => $doctor,
        'image_url' => $validatedData['image']
    ], 201);
}
}

