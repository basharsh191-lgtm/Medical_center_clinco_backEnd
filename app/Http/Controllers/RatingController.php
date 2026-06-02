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
            $rating = $this->ratingService->store(
                $request->validated(),
                $doctorId,
                Auth::id()
            );
            return response()->json([
                'message' => 'تم تسجيل تقييمك بنجاح.',
                'data'    => $rating
            ], 201);
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
