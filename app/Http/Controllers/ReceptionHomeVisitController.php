<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\HomeVisit;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceptionHomeVisitController extends Controller
{

    public function getClinicHomeVisits(Request $request)
    {
        $clinicId = Auth::user()->reception->clinic_id;

        $homeVisits = HomeVisit::where('clinic_id', $clinicId)
            ->with(['patient.user', 'doctor.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب طلبات الرعاية المنزلية للعيادة بنجاح',
            'data'    => [
                'visits'     => $homeVisits->items(),
                'pagination' => [
                    'current_page' => $homeVisits->currentPage(),
                    'last_page'    => $homeVisits->lastPage(),
                    'per_page'     => $homeVisits->perPage(),
                    'total'        => $homeVisits->total(),
                    'has_more'     => $homeVisits->hasMorePages(),
                ]
            ]
        ], 200);
    }
    public function approveAndAssignDoctor(Request $request, $id, FcmService $fcmService)
    {
        $clinicId = Auth::user()->reception?->clinic_id;

        if (!$clinicId) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب الحالي غير مرتبط بعيادة كموظف استقبال.'
            ], 403);
        }

        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $visit = HomeVisit::where('clinic_id', $clinicId)->findOrFail($id);

        $visit->update([
            'doctor_id'        => $request->doctor_id,
            'status'           => 'assigned',
            'rejection_reason' => null
        ]);

        // إرسال الإشعارات للمريض وللطبيب
        $this->sendAssignNotifications($visit, $fcmService);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الطبيب لطلب الرعاية المنزلية بنجاح'
        ], 200);
    }
    private function sendAssignNotifications(HomeVisit $visit, FcmService $fcmService)
    {
        // تحميل العلاقات المطلوبة (المريض مع أسلوب حسابه، والطبيب مع أسلوب حسابه)
        $visit->load([
            'patient.user',
            'doctor.user'
        ]);

        // 1. إرسال إشعار للمريض
        $patientUser = $visit->patient?->user;
        if ($patientUser) {
            $doctorUser = $visit->doctor?->user;
            $doctorName = trim("{$doctorUser?->name} {$doctorUser?->last_name}") ?: 'الطبيب';

            $title = 'تم قبول طلب الرعاية المنزلية 🩺';
            $body  = "تمت الموافقة على طلبك وتعيين د. {$doctorName} لمتابعة زيارتك المنزلية.";

            $data  = [
                'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                'action'        => 'OPEN_HOME_VISIT_DETAILS',
                'home_visit_id' => (string) $visit->id,
            ];

            $fcmService->sendToUser($patientUser->id, $title, $body, $data);
        }

        // 2. إرسال إشعار للطبيب المُعيَّن
        $doctorUser = $visit->doctor?->user;
        if ($doctorUser) {
            $patientUser = $visit->patient?->user;
            $patientName = trim("{$patientUser?->name} {$patientUser?->last_name}") ?: 'مريض';

            $title = 'زيارة منزلية جديدة 🏠';
            $body  = "تم تعيينك لمتابعة طلب زيارة منزلية جديد للمريض {$patientName}.";

            $data  = [
                'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                'action'        => 'OPEN_DOCTOR_HOME_VISIT_DETAILS',
                'home_visit_id' => (string) $visit->id,
            ];

            $fcmService->sendToUser($doctorUser->id, $title, $body, $data);
        }
    }
    public function rejectVisit(Request $request, $id, FcmService $fcmService)
    {
        $clinicId = Auth::user()->reception?->clinic_id;

        if (!$clinicId) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب الحالي غير مرتبط بعيادة كموظف استقبال.'
            ], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $visit = HomeVisit::where('clinic_id', $clinicId)->findOrFail($id);

        $visit->update([
            'status'           => 'cancelled',
            'rejection_reason' => $request->rejection_reason
        ]);

        // إرسال إشعار الرفض للمريض
        $this->sendRejectionNotificationToPatient($visit, $fcmService);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب بنجاح وتوثيق السبب'
        ], 200);
    }
    private function sendRejectionNotificationToPatient(HomeVisit $visit, FcmService $fcmService)
    {
        $visit->load('patient.user');

        $patientUser = $visit->patient?->user;

        if ($patientUser) {
            $title = 'اعتذار عن طلب الزيارة المنزلية ❌';
            $body  = "نعتذر منك، تعذر قبول طلب الزيارة المنزلية. السبب: {$visit->rejection_reason}";

            $data  = [
                'click_action'     => 'FLUTTER_NOTIFICATION_CLICK',
                'action'           => 'OPEN_HOME_VISIT_DETAILS',
                'home_visit_id'    => (string) $visit->id,
                'rejection_reason' => (string) $visit->rejection_reason,
            ];

            $fcmService->sendToUser($patientUser->id, $title, $body, $data);
        }
    }
    public function getRejectedVisits()
    {
        $clinicId = Auth::user()->reception->clinic_id;

        $rejectedVisits = HomeVisit::where('clinic_id', $clinicId)
            ->where('status', 'rejected')
            ->with(['patient.user'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الزيارات المرفوضة التي تتطلب إعادة تعيين',
            'data'    => $rejectedVisits
        ], 200);
    }
    public function getSingleHomeVisit($id)
    {
        // جلب رقم عيادة موظف الاستقبال الحالي
        $clinicId = Auth::user()->reception->clinic_id;

        // جلب الزيارة والتأكد أنها تابعة لعيادة الاستقبال
        $homeVisit = HomeVisit::where('clinic_id', $clinicId)
            ->with(['patient.user', 'doctor.user'])
            ->find($id);

        // في حال عدم وجود الطلب أو عدم تبعيته للعيادة
        if (!$homeVisit) {
            return response()->json([
                'success' => false,
                'message' => 'طلب الرعاية المنزلية غير موجود أو لا ينتمي لهذه العيادة'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب تفاصيل طلب الرعاية المنزلية بنجاح',
            'data'    => [
                'visit' => $homeVisit
            ]
        ], 200);
    }
    public function getAvailableDoctorsForHomeVisit(Request $request, $visitId)
    {
        $clinicId = Auth::user()->reception->clinic_id;

        // 1. جلب تفاصيل طلب الزيارة
        $homeVisit = HomeVisit::where('clinic_id', $clinicId)->find($visitId);

        if (!$homeVisit) {
            return response()->json([
                'success' => false,
                'message' => 'طلب الرعاية المنزلية غير موجود أو لا ينتمي لهذه العيادة'
            ], 404);
        }

        // 2. تحويل تاريخ الزيارة لمعرفة اسم اليوم باللغة العربية لمطابقته مع doctor_schedules
        $daysTranslation = [
            'Saturday'  => 'السبت',
            'Sunday'    => 'الأحد',
            'Monday'    => 'الإثنين',
            'Tuesday'   => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday'  => 'الخميس',
            'Friday'    => 'الجمعة',
        ];

        $dayNameInEnglish = \Carbon\Carbon::parse($homeVisit->visit_date)->format('l');
        $dayInArabic = $daysTranslation[$dayNameInEnglish] ?? null;

        $visitStartTime = $homeVisit->start_time;
        $visitEndTime   = $homeVisit->end_time;

        // 3. جلب الأطباء المتاحين التابعين لنفس العيادة ولديهم جدول يناسب وقت اليوم المطلوبة
        $availableDoctors = Doctor::where('clinic_id', $clinicId)
            ->whereHas('schedules', function ($query) use ($dayInArabic, $visitStartTime, $visitEndTime) {
                $query->where('day', $dayInArabic)
                    ->where('is_active', true)
                    ->where('start_time', '<=', $visitStartTime)
                    ->where('end_time', '>=', $visitEndTime);
            })
            ->with('user:id,name,phone') // جلب بيانات المستخدم المترابطة مع الطبيب
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة الأطباء المتاحين لفرز الزيارة بنجاح',
            'data'    => [
                'visit_id'          => $homeVisit->id,
                'day'               => $dayInArabic,
                'available_doctors' => $availableDoctors
            ]
        ], 200);
    }
    public function getActiveHomeVisitsTracking(Request $request)
    {
        // جلب رقم عيادة موظف الاستقبال الحالي
        $clinicId = Auth::user()->reception->clinic_id;

        // جلب الزيارات النشطة (في الطريق أو وصل الموقع)
        $activeVisits = HomeVisit::where('clinic_id', $clinicId)
            ->whereIn('status', ['on_the_way', 'arrived'])
            ->with([
                'patient.user:id,name,phone',
                'doctor.user:id,name,phone'
            ])
            ->orderBy('visit_date', 'asc')
            ->get();

        // معالجة البيانات لإضافة مؤشرات التأخير والإحداثيات
        $formattedVisits = $activeVisits->map(function ($visit) {
            // حساب الوقت الحالي ومقارنته بوقت بداية الزيارة المتوقع
            $now = \Carbon\Carbon::now();
            $expectedStartTime = \Carbon\Carbon::parse($visit->visit_date->format('Y-m-d') . ' ' . $visit->start_time);

            // التحقق مما إذا كانت الزيارة متأخرة عن وقتها المحدد
            $isDelayed = $now->greaterThan($expectedStartTime) && $visit->status !== 'arrived';
            $delayInMinutes = $isDelayed ? (int) $expectedStartTime->diffInMinutes($now) : 0;

            return [
                'id'             => $visit->id,
                'status'         => $visit->status, // on_the_way OR arrived
                'visit_date'     => $visit->visit_date,
                'start_time'     => $visit->start_time,
                'end_time'       => $visit->end_time,
                'patient'        => [
                    'id'    => $visit->patient_id,
                    'name'  => $visit->patient->user->name ?? null,
                    'phone' => $visit->patient->user->phone ?? null,
                ],
                'doctor'         => [
                    'id'    => $visit->doctor_id,
                    'name'  => $visit->doctor->user->name ?? null,
                    'phone' => $visit->doctor->user->phone ?? null,
                ],
                'location'       => [
                    'lat' => $visit->location_lat,
                    'lng' => $visit->location_lng,
                ],
                'tracking_info'  => [
                    'is_delayed'       => $isDelayed,
                    'delay_in_minutes' => $delayInMinutes,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب تتبع الزيارات النشطة حالياً بنجاح',
            'data'    => [
                'total_active_visits' => $formattedVisits->count(),
                'visits'              => $formattedVisits
            ]
        ], 200);
    }
}
