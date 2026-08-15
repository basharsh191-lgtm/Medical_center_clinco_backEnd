<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabOrderRequest;
use App\Http\Requests\UpdateLabOrderRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Services\MedicalRecordService;
use Illuminate\Support\Facades\Auth;
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
    public function updateLabOrderDoctor(UpdateLabOrderRequest $request, $id)
    {
        // 1. استدعاء السيرفيس لتعديل الطلب وتمرير المعرف والبيانات القادمة من الـ Request
        // يمكنك أيضاً استخدام Route Model Binding بتمرير (LabOrder $labOrder) مباشرة للتابع
        $labOrder = $this->medicalRecordService->updateOrder($id, $request->validated());

        // 2. التحقق مما إذا كان الطلب موجوداً أو إذا فشل التعديل لأي سبب
        if (!$labOrder) {
            return response()->json([
                'status'  => 'error',
                'message' => 'طلب المختبر غير موجود، أو لا يمكن تعديله (قد يكون مكتمل أو ملغى مسبقاً).'
            ], 404);
        }

        // 3. إرجاع الاستجابة بنجاح مع كود 200 OK
        return response()->json([
            'status'  => 'success',
            'message' => 'تم تحديث طلب المختبر بنجاح',
            'data'    => $labOrder
        ], 200);
    }
    public function editLabOrder($id)
{
    $order = LabOrder::with('tests')->findOrFail($id);

    return response()->json([
        'order' => $order,
        'selected_tests' => $order->tests->pluck('test_name')
    ]);
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

}
