<?php

namespace App\Http\Controllers;

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

    public function storeRequest(Request $request)
    {
        $patient = patient::where('user_id',Auth::id())->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على ملف مريض لهذا الحساب.'
            ], 404);
        }

        // التحقق من صحة البيانات القادمة من الفلاتر
        $validatedData = $request->validate([
            'specialization_id' => 'required|exists:specializations,id',
            'visit_date'        => 'required|date_format:Y-m-d H:i:s|after:now',
            'location_lat'      => 'required|numeric|between:-90,90',
            'location_lng'      => 'required|numeric|between:-180,180',
        ]);

        // استدعاء السيرفيس
        $result = $this->homeVisitService->createPatientRequest($patient, $validatedData);

        return response()->json($result['response'], $result['status_code']);
    }
    }
