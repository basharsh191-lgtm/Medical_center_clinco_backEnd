<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHomeLabOrderRequest;
use App\Http\Requests\UpdateLabOrderRequest;
use App\Models\LabOrder;
use App\Services\MedicalRecordService;
use Illuminate\Http\Request;

class HomeCareLabOrderController extends Controller
{
    protected $medicalRecordService;
    public function __construct(MedicalRecordService $medicalRecordService)
    {
        $this->medicalRecordService = $medicalRecordService;
    }
    // إنشاء طلب للزيارة المنزلية
    public function store(StoreHomeLabOrderRequest $request, $visitId)
    {
        $data = $request->validated();
        $data['home_visit_id'] = $visitId; // ربط الطلب بالزيارة المنزلية

        $labOrder = $this->medicalRecordService->createHomeOrder($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إنشاء طلب المختبر للزيارة المنزلية بنجاح',
            'data'    => $labOrder
        ], 201);
    }
    // عرض بيانات الطلب للتعديل
    public function edit($id)
    {
        $order = LabOrder::with('tests')->findOrFail($id);

        return response()->json([
            'order'          => $order,
            'selected_tests' => $order->tests->pluck('test_name')
        ], 200);
    }
    // تحديث طلب المختبر
    public function update(UpdateLabOrderRequest $request, $id)
    {
        $labOrder = $this->medicalRecordService->updateOrder($id, $request->validated());

        if (!$labOrder) {
            return response()->json([
                'status'  => 'error',
                'message' => 'طلب المختبر غير موجود، أو لا يمكن تعديله.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تحديث طلب المختبر بنجاح',
            'data'    => $labOrder
        ], 200);
    }
    // إلغاء طلب المختبر
    public function cancel($id)
    {
        $order = LabOrder::where('id', $id)
            ->where('overall_status', 'pending')
            ->firstOrFail();

        $order->update([
            'overall_status' => 'cancelled'
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إلغاء الطلب بنجاح'
        ], 200);
    }
}
