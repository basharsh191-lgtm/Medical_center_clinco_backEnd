<?php

namespace App\Http\Controllers;

use App\Http\Requests\DoctorRequest;
use App\Http\Requests\ReceptionRequest;
use App\Models\DeviceTokens;
use App\Models\User;
use App\Services\FcmService;
use App\Services\StaffService;
use App\UploadFileTrait;

class StaffController extends Controller
{
protected $staffService;
protected $fcmService;
use UploadFileTrait;

public function __construct(StaffService $staffService, FcmService $fcmService)
    {
        $this->staffService = $staffService;
        $this->fcmService = $fcmService;
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
public function storeDoctor(DoctorRequest $request,FcmService $fcmService)
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
    $title = 'انضم طبيب جديد  لمنزلنا الطبي 🌟';
    $body = "نرحب بالدكتور {$doctor->name} (اختصاص {$doctor->specialty})  clinco في المنصة.";
    $data = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'action' => 'OPEN_DOCTOR_PROFILE',
        'doctor_id' => (string) $doctor->id,
    ];
    User::whereHas('deviceTokens')->select('id')->chunk(100, function ($users) use ($fcmService, $title, $body, $data) {
        foreach ($users as $user) {
            $fcmService->sendToUser($user->id, $title, $body, $data);
        }
    });
    return response()->json([
        'message' => 'Doctor created successfully',
        'data' => $doctor,
        'image_url' => $validatedData['image']
    ], 201);
}
}

