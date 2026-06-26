<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\HomeVisits;
use App\Models\Reception;
use App\Services\DoctorScheduleService;
use App\Services\ReceptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceptionistScheduleController extends Controller
{
    protected $scheduleService;
    protected $checkInService;

    public function __construct(DoctorScheduleService $scheduleService, ReceptionService $checkInService)
    {
        $this->scheduleService = $scheduleService;
        $this->checkInService = $checkInService;
    }
public function storeSchedule(StoreScheduleRequest $request)
    {
        $validated = $request->validated();
        try {
            $schedule = $this->scheduleService->createOrUpdateSchedule($validated);
            return response()->json([
                'success' => true,
                'message' => 'تم تحديد مواعيد دوام الطبيب بنجاح.',
                'data'    => $schedule
            ], 201);
        } catch (\Exception $e) {
            // استخدام الكود 403 أو 422 حسب نوع الخطأ المرمي من الخدمة
            $statusCode = $e->getCode() >= 400 ? $e->getCode() : 422;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ], $statusCode);
        }
    }
    public function getMyClinicDoctors()
    {
        try {
            $doctors = $this->scheduleService->getClinicDoctors();
            $formattedDoctors = $doctors->map(function ($doctor) {
                return [
                    'doctor_id'      => $doctor->id,
                    'name'           => $doctor->user->name,
                    'last_name'      =>$doctor->user->last_name,
                    'specialization_id' => $doctor->specialization_id,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'تم جلب أطباء العيادة بنجاح.',
                'data'    => $formattedDoctors
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ], 403);
        }
    }
    public function checkInByPatientQR(Request $request)
        {
            $request->validate([
                'qr_token' => 'required|uuid|exists:patients,qr_token',
            ]);
            $user = Auth::user()->load('reception');
            if (!$user->reception) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'هذا المستخدم ليس موظف استقبال أو غير مرتبط بعيادة.'
                ], 400);
            }
            $receptionistClinicId = $user->reception->clinic_id;
            $appointment = $this->checkInService->checkInByQr($request->qr_token, $receptionistClinicId);
            return response()->json([
                'status' => 'success',
                'message' => 'تم تسجيل وصول المريض بنجاح وتحديث الحالة.',
            ]);
    }
    public function changeStatus(Request $request, $id)
{
    // 1. التحقق من صحة البيانات المرسلة
    $request->validate([
        'status' => 'required|in:assigned,cancelled'
    ], [
        'status.in' => 'الحالة يجب أن تكون إما موافقة (assigned) أو رفض (cancelled).'
    ]);

    try {
        // 2. جلب ملف الاستقبال المرتبط بالمستخدم الحالي (Auth)
        $receptionist = Reception::where('user_id', Auth::id())->first();

        // التحقق من أن المستخدم لديه حساب موظف استقبال فعلياً
        if (!$receptionist) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذا الإجراء، الحساب ليس موظف استقبال.'
            ], 403); // 403 Forbidden
        }

        // 3. جلب الحجز مع بيانات الطبيب المرتبط به لمعرفة عيادته
        // نستخدم with('doctor') لجلب العلاقة وتقليل استعلامات الداتا بيز
        $homeVisit = HomeVisits::with('doctor')->findOrFail($id);

        // 4. التحقق من أن الاستقبال والطبيب يتبعان لنفس العيادة
        if ($receptionist->clinic_id !== $homeVisit->doctor->clinic_id) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، لا يمكنك إدارة هذا الحجز لأنه يتبع لطبيب في عيادة أخرى.'
            ], 403);
        }

        // 5. التأكد من أن الحجز ما زال "قيد الانتظار"
        if ($homeVisit->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل حالة هذا الحجز لأنه لم يعد قيد الانتظار.'
            ], 400); // 400 Bad Request
        }

        // 6. تحديث الحالة
        $homeVisit->status = $request->status;
        $homeVisit->save();

        $message = $request->status === 'assigned'
            ? 'تمت الموافقة على الحجز وتعيينه للطبيب بنجاح.'
            : 'تم رفض الحجز وإلغاؤه.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $homeVisit
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'لم يتم العثور على الحجز المطلوب.'
        ], 404);
    } catch (\Exception $e) {
        \Log::error('Error changing home visit status: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ داخلي، يرجى المحاولة لاحقاً.'
        ], 500);
    }
}
public function getClinicHomeVisits(Request $request)
{
    $clinicId = Auth::user()->clinic_id;

    $homeVisits = HomeVisits::whereHas('doctor', function ($query) use ($clinicId) {
            $query->where('clinic_id', $clinicId);
        })
        ->with(['patient', 'doctor'])
        ->orderBy('visit_date', 'desc')
        ->orderBy('start_time', 'desc')
        ->paginate(15);
    return response()->json([
        'success' => true,
        'message' => 'تم جلب طلبات الرعاية المنزلية للعيادة بنجاح',
        'data'    => $homeVisits
    ]);
}
}
