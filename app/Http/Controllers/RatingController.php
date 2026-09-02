<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Rating;
use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{

    protected $ratingService;

    public function __construct(RatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }
    public function storeRating(StoreRatingRequest $request, $doctorId)
    {
        try {
            $rating = $this->ratingService->store(
                $request->validated(),
                $doctorId,
                Auth::id()
            );

            // استجابة النجاح
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل تقييمك بنجاح.',
                'data'    => $rating
            ], 201);
        } catch (\Exception $e) {
            // التحقق من كود الخطأ ليكون رقم HTTP صالح (أو استخدام 400 كافتراضي)
            $statusCode = $e->getCode();
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 400;
            }

            // استجابة الفشل (بدون trace)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
    public function showAllRatingsDoctors()
    {
        $doctors = Doctor::withAvg('ratings as average_rating', 'stars')->get();
        return response()->json([
            'data' => $doctors
        ]);
    }
    public function showDoctorRatings($id)
    {
        $doctor = Doctor::withAvg('ratings as average_rating', 'stars')
            ->findOrFail($id);

        return response()->json([
            'data' => $doctor
        ]);
    }
}
