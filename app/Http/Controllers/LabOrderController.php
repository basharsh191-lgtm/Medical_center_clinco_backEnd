<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabOrderRequest;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\patient;
use App\Services\MedicalRecordService;
use Auth;

class LabOrderController extends Controller
{

    protected $medicalRecordService;

    public function __construct(MedicalRecordService $medicalRecordService)
    {
        $this->medicalRecordService = $medicalRecordService;
    }

public function storeLabOrderDoctor(StoreLabOrderRequest $request)
    {
        $labOrder = $this->medicalRecordService->createOrder($request->validated());
        return response()->json([
            'status'  => 'success',
            'message' => 'تم إنشاء طلب المختبر بنجاح',
            'data'    => $labOrder
        ], 201);
    }
public function getMyLabOrders()
{
    $user = Auth::user();
    $patient = patient::where('user_id', $user->id)->first();
    if (!$patient) {
        return response()->json([
            'message' => 'الحساب الحالي ليس مريض'
        ], 403);
    }
    $labOrders = LabOrder::whereHas('appointment', function ($q) use ($patient) {
            $q->where('patient_id', $patient->id);
        })
        ->with([
            'tests'
        ])
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $labOrders
    ]);
}
public function cancelLabOrder($id)
{
    $doctor = Doctor::where('user_id', Auth::id())->first();

    $order = LabOrder::where('id', $id)
        ->where('overall_status', 'pending')
        ->firstOrFail();

    $order->update([
        'overall_status' => 'cancelled'
    ]);
    return response()->json([
        'message' => 'تم إلغاء الطلب'
    ]);
}
}
