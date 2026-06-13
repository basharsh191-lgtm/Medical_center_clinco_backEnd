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
    if ($request->hasFile('image')) {
    $path = $this->upload($request->file('image'), 'doctor_picture', 'public');
    $validated['image'] = $path;
    } else {
        $validated['image'] = null;
    }
    $doctor = $this->staffService->createStaff(
        $request->validated(),
        'doctor'
    );

    return response()->json([
        'message' => 'Doctor created successfully',
        'data' => $doctor,
        'image_url' => $path ? asset('storage/' . $path) : null
            ], 201);
}
// public function storeDoctors(DoctorRequest $request)
// {
//     // 1. استخراج البيانات المفحوصة في مصفوفة أولاً لنستطيع التعديل عليها
//     $validatedData = $request->validated();

//     // 2. استقبال اسم السيرفر (مع استخدام public كافتراضي)
//     $diskName = $request->storage_disk ?? 'public';

//     $path = null;
//     $imageUrl = null;

//     if ($request->hasFile('image')) {
//         // 3. تمرير اسم السيرفر الديناميكي لدالة الرفع الخاصة بك
//         $path = $this->upload($request->file('image'), 'doctor_picture', $diskName);

//         // تحديث مسار الصورة داخل المصفوفة
//         $validatedData['image'] = $path;

//         // (خطوة هندسية): إذا أضفت حقل image_disk لجدول الأطباء لحفظ مكان الصورة
//         // $validatedData['image_disk'] = $diskName;

//         // توليد الرابط الديناميكي
//         $imageUrl = \Illuminate\Support\Facades\Storage::disk($diskName)->url($path);
//     } else {
//         $validatedData['image'] = null;
//     }

//     // 4. تمرير المصفوفة المعدلة ($validatedData) إلى الـ Service
//     $doctor = $this->staffService->createStaff(
//         $validatedData,
//         'doctor'
//     );

//     return response()->json([
//         'message'   => 'Doctor created successfully',
//         'data'      => $doctor,
//         'image_url' => $imageUrl
//     ], 201);
// }
}

