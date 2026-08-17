<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHomeVisitRequest;
use App\Http\Requests\UpdateHomeVisitRequest;
use App\Models\HomeVisit;
use App\Models\Patient;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeVisitController extends Controller
{
public function requestHomeVisit(StoreHomeVisitRequest $request, FcmService $fcmService)
{
    // 1. جلب سجل المريض
    $patient = Patient::where('user_id', Auth::id())->first();

    if (!$patient) {
        return response()->json([
            'success' => false,
            'message' => 'لم يتم العثور على بيانات المريض الخاصة بهذا الحساب'
        ], 404);
    }

    // 2. إنشاء طلب الزيارة المنزلية
    $homeVisit = $patient->homeVisits()->create($request->validated());

    // 3. إرسال الإشعار لموظف الاستقبال الخاص بالعيادة
    $this->sendNotificationToReception($homeVisit, $fcmService);

    return response()->json([
        'success' => true,
        'message' => 'تم إرسال طلب الرعاية المنزلية بنجاح',
        'data'    => $homeVisit
    ], 201);
}

/**
 * دالة مساعدة لإرسال الإشعار للاستقبال الخاص بالعيادة
 */
private function sendNotificationToReception($homeVisit, FcmService $fcmService)
{
    // تحميل كافة العلاقات المطلوبة دفعة واحدة
    $homeVisit->load([
        'clinic.reception.user',
        'patient.user' // تحميل المريض وحسابه لتوفير الاسم
    ]);

    // جلب موظف الاستقبال
    $receptionUser = $homeVisit->clinic?->reception?->user;

    if ($receptionUser) {
        // جلب الاسم من الـ User المربوط بالمريض (أو fallback في حال عدم وجوده)
        $firstName   = $homeVisit->patient?->user?->name;
        $lastName    = $homeVisit->patient?->user?->last_name;
        $patientName = trim("{$firstName} {$lastName}") ?: 'مريض';
        $title = 'طلب زيارة منزلية جديد 🏠';
        $body  = "قام المريض {$patientName} بتقديم طلب رعاية منزلية جديد.";

        $data  = [
            'click_action'   => 'FLUTTER_NOTIFICATION_CLICK',
            'action'         => 'OPEN_HOME_VISIT_DETAILS',
            'home_visit_id'  => (string) $homeVisit->id,
            'clinic_id'      => (string) $homeVisit->clinic_id,
        ];

        // إرسال الإشعار
        $fcmService->sendToUser($receptionUser->id, $title, $body, $data);
    }
}
public function updateHomeVisit(UpdateHomeVisitRequest $request, $id, FcmService $fcmService)
{
    // 1. التحقق من أن المستخدم مسجل كمريض
    $patientId = Auth::user()->patient?->id;

    if (!$patientId) {
        return response()->json([
            'success' => false,
            'message' => 'هذا الحساب غير معرف كمريض في النظام.',
        ], 403);
    }

    // 2. جلب الطلب بناءً على الـ ID والتأكد من أنه يخص هذا المريض
    $homeVisit = HomeVisit::where('id', $id)->where('patient_id', $patientId)->first();

    if (!$homeVisit) {
        return response()->json([
            'success' => false,
            'message' => 'لم يتم العثور على الطلب، أو أنك لا تملك صلاحية تعديله.'
        ], 404);
    }

    // 3. منع التعديل في حال تم تعيين طبيب أو تغيير حالة الطلب
    if ($homeVisit->status !== 'pending') {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن تعديل الطلب لأن حالته الحالية لم تعد قيد الانتظار.'
        ], 400);
    }

    // 4. تحديث الطلب بالبيانات الجديدة الممررة في الـ Request
    $homeVisit->update($request->validated());

    // 5. إرسال إشعار التعديل لموظف الاستقبال
    $this->sendUpdateNotificationToReception($homeVisit, $fcmService);

    return response()->json([
        'success' => true,
        'message' => 'تم تعديل طلب الرعاية المنزلية بنجاح',
        'data'    => $homeVisit
    ], 200);
}


public function cancelHomeVisit($id, FcmService $fcmService)
{
    // 1. التحقق من أن المستخدم الحالي مسجل كمريض
    $patientId = Auth::user()->patient?->id;

    if (!$patientId) {
        return response()->json([
            'success' => false,
            'message' => 'هذا الحساب غير معرف كمريض في النظام.',
        ], 403);
    }

    // 2. جلب الطلب بناءً على الـ ID والتأكد من أنه يخص المريض
    $homeVisit = HomeVisit::where('id', $id)->where('patient_id', $patientId)->first();

    if (!$homeVisit) {
        return response()->json([
            'success' => false,
            'message' => 'لم يتم العثور على الطلب، أو أنك لا تملك صلاحية إلغائه.'
        ], 404);
    }

    // 3. التأكد من أن الطلب لم يكتمل أو يُلغى مسبقاً
    if (in_array($homeVisit->status, ['completed', 'cancelled'])) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن إلغاء هذا الطلب لأن حالته الحالية هي: ' . $homeVisit->status
        ], 400);
    }

    // 4. تغيير حالة الطلب إلى ملغي (cancelled)
    $homeVisit->update(['status' => 'cancelled']);

    // 5. إرسال إشعار الإلغاء لموظف الاستقبال
    $this->sendCancelNotificationToReception($homeVisit, $fcmService);

    return response()->json([
        'success' => true,
        'message' => 'تم إلغاء طلب الرعاية المنزلية بنجاح',
        'data'    => $homeVisit
    ], 200);
}


/**
 * دالة مساعدة لإرسال إشعار عند تعديل الطلب
 */
    private function sendUpdateNotificationToReception(HomeVisit $homeVisit, FcmService $fcmService)
    {
        $homeVisit->load(['clinic.reception.user', 'patient.user']);

        $receptionUser = $homeVisit->clinic?->reception?->user;

        if ($receptionUser) {
            $user        = $homeVisit->patient?->user;
            $patientName = trim("{$user?->name} {$user?->last_name}") ?: 'مريض';

            $title = 'تحديث على طلب رعاية منزلية ✏️';
            $body  = "قام المريض {$patientName} بتحديث تفاصيل طلب الرعاية المنزلية رقم #{$homeVisit->id}.";

            $data  = [
                'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                'action'        => 'OPEN_HOME_VISIT_DETAILS',
                'home_visit_id' => (string) $homeVisit->id,
                'clinic_id'     => (string) $homeVisit->clinic_id,
            ];

            $fcmService->sendToUser($receptionUser->id, $title, $body, $data);
        }
    }

    /**
     * دالة مساعدة لإرسال إشعار عند إلغاء الطلب
     */
    private function sendCancelNotificationToReception(HomeVisit $homeVisit, FcmService $fcmService)
    {
        $homeVisit->load(['clinic.reception.user', 'patient.user']);

        $receptionUser = $homeVisit->clinic?->reception?->user;

        if ($receptionUser) {
            $user        = $homeVisit->patient?->user;
            $patientName = trim("{$user?->name} {$user?->last_name}") ?: 'مريض';

            $title = 'إلغاء طلب رعاية منزلية ❌';
            $body  = "قام المريض {$patientName} بإلغاء طلب الرعاية المنزلية رقم #{$homeVisit->id}.";

            $data  = [
                'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                'action'        => 'OPEN_HOME_VISIT_DETAILS',
                'home_visit_id' => (string) $homeVisit->id,
                'clinic_id'     => (string) $homeVisit->clinic_id,
            ];

            $fcmService->sendToUser($receptionUser->id, $title, $body, $data);
        }
    }

    public function getPatientHomeVisits(Request $request)
    {
        // 1. جلب الـ patient_id الخاص بالمريض الحالي الذي قام بتسجيل الدخول
        $patientId = Auth::user()->patient->id ?? null;

        if (!$patientId) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب غير معرف كمريض في النظام.',
            ], 403);
        }

        // 2. جلب كافة زيارات المريض مع تحميل بيانات الطبيب وحسابه في جدول الـ users
        $visits = HomeVisit::where('patient_id', $patientId)
            ->with(['doctor.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // 3. تنظيف البيانات لترجع بالشكل المطلوب تماماً للـ Front-end
        $customVisits = collect($visits->items())->map(function ($visit) {
            return [
                'visit_info' => [
                    'id'               => $visit->id,
                    'visit_date'       => $visit->visit_date,
                    'start_time'       => $visit->start_time,
                    'end_time'         => $visit->end_time,
                    'status'           => $visit->status, // pending, accepted/approved, rejected, completed
                    'rejection_reason' => $visit->rejection_reason, // يظهر فقط في حال الرفض
                    'created_at'       => $visit->created_at->format('Y-m-d H:i:s'),
                ],
                // إذا وافق الريسبشن وعيّن طبيب، ستظهر بياناته هنا، وإلا سترجع null
                'doctor' => $visit->doctor && $visit->doctor->user ? [
                    'id'    => $visit->doctor->id,
                    'name'  => $visit->doctor->user->name,
                    'phone' => $visit->doctor->user->phone,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب طلبات الرعاية المنزلية الخاصة بك بنجاح',
            'data'    => [
                'visits'     => $customVisits,
                'pagination' => [
                    'current_page' => $visits->currentPage(),
                    'last_page'    => $visits->lastPage(),
                    'per_page'     => $visits->perPage(),
                    'total'        => $visits->total(),
                    'has_more'     => $visits->hasMorePages(),
                ]
            ]
        ], 200);
    }
}
