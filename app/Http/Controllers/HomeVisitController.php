<?php

namespace App\Http\Controllers;

use App\Http\Requests\HomeVisitPatientRequest;
use App\Models\patient;
use App\Services\HomeVisitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeVisitController extends Controller
{
protected $homeVisitService;

    public function __construct(HomeVisitService  $homeVisitService)
    {
        $this->homeVisitService = $homeVisitService;
    }

public function storeRequest(HomeVisitPatientRequest $request)
    {
        // جلب ملف المريض المرتبط بالمستخدم الحالي
        $patient = Patient::where('user_id', Auth::id())->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على ملف مريض لهذا الحساب.'
            ], 404);
        }

        try {
            // استدعاء السيرفيس لإنشاء الحجز
            $homeVisit = $this->homeVisitService->bookHomeVisit($patient, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم حجز موعد الرعاية المنزلية بنجاح، بانتظار موافقة الاستقبال.',
                'data'    => $homeVisit
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422); // 422 في حال فشل التحقق من توفر السلوت برمجياً
        }
    }
    }
