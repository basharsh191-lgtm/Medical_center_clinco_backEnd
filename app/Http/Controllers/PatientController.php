<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientRequest;
use App\Models\patient;
use App\Services\PatientService;
use App\UploadFileTrait;

use Illuminate\Http\Request;

class PatientController extends Controller
{
protected $PatientService;
use UploadFileTrait;
    public function __construct(PatientService $PatientService)
    {
        $this->PatientService = $PatientService;
    }
public function storePatient(PatientRequest $request)
{
    $validated = $request->validated();
    $path = null;
    if ($request->hasFile('image')) {
        $path = $this->upload($request->file('image'), 'profile', 'public');
        $validated['image'] = $path;
    } else {
        $validated['image'] = null;
    }
    $patient = $this->PatientService->createPatient($validated);
    if (!$patient) {
        return response()->json([
            'success' => false,
            'message' => 'هذا المستخدم لديه حساب مريض بالفعل.'
        ], 422);
    }
    return response()->json([
        'success' => true,
        'message' => 'تم إنشاء ملف المريض بنجاح',
        'data' => $patient,
        'image_url' => $path ? asset('storage/' . $path) : null
    ], 201);
}
public function showPatient()
    {
        return $this->PatientService->getMyProfile();
}

}
