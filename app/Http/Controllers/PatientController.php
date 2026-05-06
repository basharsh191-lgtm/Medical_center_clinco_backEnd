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
        if($request->hasFile('image'))
        {
            $path=$this->upload($request->file('image'),'profile','public');
            $validated['image']=$path;
        }
    $validated=$request->validated();
    $patient=$this->PatientService->createPatient($validated);
        return response()->json([
                'success' => true,
                'data' => $patient,
                'image_url' => asset('storage/' . $path)
            ], 201);
    }
    public function showPatient($id)
    {
        return $this->PatientService->getPatientById($id);
    }
}
