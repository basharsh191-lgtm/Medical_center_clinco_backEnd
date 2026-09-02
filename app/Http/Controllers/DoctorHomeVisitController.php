<?php

namespace App\Http\Controllers;

use App\Models\HomeVisit;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorHomeVisitController extends Controller
{
    public function getMyHomeVisits(Request $request)
    {
        $doctorId = Auth::user()->doctorProfile->id;

        $visits = HomeVisit::where('doctor_id', $doctorId)
            ->whereIn('status', ['assigned', 'accepted', 'on_the_way', 'arrived'])
            ->with(['patient.user'])
            ->orderBy('visit_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب زيارات الطبيب الحالية بنجاح',
            'data'    => $visits
        ], 200);
    }
    public function acceptVisit($id, FcmService $fcmService)
    {
        $doctorProfile = Auth::user()?->doctorProfile;

        if (!$doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'حساب المستخدم غير مرتبط بملف طبيب.'
            ], 403);
        }

        $visit = HomeVisit::find($id);

        if (!$visit) {
            return response()->json([
                'success' => false,
                'message' => 'الزيارة المنزلية غير موجودة.'
            ], 404);
        }

        if ($visit->doctor_id !== $doctorProfile->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بقبول هذه الزيارة (مخصصة لطبيب آخر).'
            ], 403);
        }

        if ($visit->status !== 'assigned' && $visit->status !== 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن قبول هذه الزيارة لأن حالتها الحالية هي: ' . $visit->status
            ], 422);
        }

        $visit->update([
            'status' => 'accepted'
        ]);

        $this->sendAcceptNotifications($visit, $fcmService);

        return response()->json([
            'success' => true,
            'message' => 'تم قبول الزيارة بنجاح، يمكنك الآن بدء التوجه عند الاستعداد'
        ], 200);
    }
    private function sendAcceptNotifications(HomeVisit $visit, FcmService $fcmService)
    {
        $visit->load([
            'patient.user',
            'clinic.reception.user',
            'doctor.user'
        ]);

        $doctorUser = $visit->doctor?->user;
        $doctorName = trim("{$doctorUser?->name} {$doctorUser?->last_name}") ?: 'الطبيب';

        // 1. إرسال إشعار للمريض
        $patientUser = $visit->patient?->user;
        if ($patientUser) {
            $title = 'الطبيب يتجهز لزيارتك 🩺';
            $body  = "وافق د. {$doctorName} على طلب الزيارة المنزلية وسيستعد للتوجه إليك قريباً.";

            $data  = [
                'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                'action'        => 'OPEN_HOME_VISIT_DETAILS',
                'home_visit_id' => (string) $visit->id,
                'status'        => 'accepted',
            ];

            $fcmService->sendToUser($patientUser->id, $title, $body, $data);
        }

        // 2. إرسال إشعار لموظف الاستقبال (Reception)
        $receptionUser = $visit->clinic?->reception?->user;
        if ($receptionUser) {
            $patientUser = $visit->patient?->user;
            $patientName = trim("{$patientUser?->name} {$patientUser?->last_name}") ?: 'مريض';

            $title = 'تم قبول الزيارة من قبل الطبيب ✅';
            $body  = "قام د. {$doctorName} بتقديم الموافقة على زيارة المريض {$patientName}.";

            $data  = [
                'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                'action'        => 'OPEN_HOME_VISIT_DETAILS',
                'home_visit_id' => (string) $visit->id,
                'clinic_id'     => (string) $visit->clinic_id,
                'status'        => 'accepted',
            ];

            $fcmService->sendToUser($receptionUser->id, $title, $body, $data);
        }
    }
    public function rejectVisit(Request $request, $id, FcmService $fcmService)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $doctorProfile = Auth::user()?->doctorProfile;

        if (!$doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'حساب المستخدم غير مرتبط بملف طبيب.'
            ], 403);
        }

        $visit = HomeVisit::where('id', $id)
            ->where('doctor_id', $doctorProfile->id)
            ->where('status', 'assigned')
            ->firstOrFail();

        $this->sendDoctorRejectionNotificationToReception($visit, $request->rejection_reason, $fcmService);

        $visit->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'doctor_id'        => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الاعتذار عن الزيارة وتحديث الحالة للاستقبال'
        ], 200);
    }
    private function sendDoctorRejectionNotificationToReception(HomeVisit $visit, string $reason, FcmService $fcmService)
    {
        // تحميل علاقات الاستقبال والطبيب
        $visit->load([
            'clinic.reception.user',
            'doctor.user'
        ]);

        $receptionUser = $visit->clinic?->reception?->user;

        if ($receptionUser) {
            $doctorUser = $visit->doctor?->user;
            $doctorName = trim("{$doctorUser?->name} {$doctorUser?->last_name}") ?: 'الطبيب';

            $title = 'اعتذار طبيب عن زيارة منزلية ⚠️';
            $body  = "اعتذر د. {$doctorName} عن الزيارة رقم #{$visit->id}. السبب: {$reason}";

            $data  = [
                'click_action'     => 'FLUTTER_NOTIFICATION_CLICK',
                'action'           => 'OPEN_HOME_VISIT_DETAILS',
                'home_visit_id'    => (string) $visit->id,
                'clinic_id'        => (string) $visit->clinic_id,
                'rejection_reason' => (string) $reason,
                'status'           => 'rejected',
            ];

            $fcmService->sendToUser($receptionUser->id, $title, $body, $data);
        }
    }
    public function startVisit($id, FcmService $fcmService)
    {
        $doctorProfile = Auth::user()?->doctorProfile;

        if (!$doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'حساب المستخدم غير مرتبط بملف طبيب.'
            ], 403);
        }

        $visit = HomeVisit::where('id', $id)
            ->where('doctor_id', $doctorProfile->id)
            ->where('status', 'accepted')
            ->firstOrFail();

        $visit->update([
            'status' => 'on_the_way'
        ]);

        $this->sendStar9yMnTm4NSzvG9rrwjM2ec8xZgh1cafXH8($visit, $fcmService);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير حالة الزيارة إلى: في الطريق إلى المريض'
        ], 200);
    }
    private function sendStar9yMnTm4NSzvG9rrwjM2ec8xZgh1cafXH8(HomeVisit $visit, FcmService $fcmService)
    {
        $visit->load([
            'patient.user',
            'doctor.user'
        ]);

        $patientUser = $visit->patient?->user;

        if ($patientUser) {
            $doctorUser = $visit->doctor?->user;
            $doctorName = trim("{$doctorUser?->name} {$doctorUser?->last_name}") ?: 'الطبيب';

            $title = 'الطبيب في الطريق إليك 🚗';
            $body  = "انطلق د. {$doctorName} الان من العيادة وهو بالاتجاه إلى منزلك.";

            $data  = [
                'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                'action'        => 'OPEN_HOME_VISIT_DETAILS',
                'home_visit_id' => (string) $visit->id,
                'status'        => 'on_the_way',
            ];

            $fcmService->sendToUser($patientUser->id, $title, $body, $data);
        }
    }
    public function arriveVisit($id, FcmService $fcmService)
    {
        $doctorProfile = Auth::user()?->doctorProfile;

        if (!$doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'حساب المستخدم غير مرتبط بملف طبيب.'
            ], 403);
        }

        $visit = HomeVisit::where('id', $id)
            ->where('doctor_id', $doctorProfile->id)
            ->where('status', 'on_the_way')
            ->firstOrFail();

        $visit->update([
            'status' => 'arrived'
        ]);

        $this->sendAr9yMnTm4NSzvG9rrwjM2ec8xZgh1cafXH8($visit, $fcmService);

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد الوصول لمنزل المريض، يمكنك البدء بالفحص وتمرير السجل الطبي'
        ], 200);
    }
    private function sendAr9yMnTm4NSzvG9rrwjM2ec8xZgh1cafXH8(HomeVisit $visit, FcmService $fcmService)
    {
        $visit->load([
            'clinic.reception.user',
            'doctor.user',
            'patient.user'
        ]);

        $receptionUser = $visit->clinic?->reception?->user;

        if ($receptionUser) {
            $doctorUser  = $visit->doctor?->user;
            $doctorName  = trim("{$doctorUser?->name} {$doctorUser?->last_name}") ?: 'الطبيب';

            $patientUser = $visit->patient?->user;
            $patientName = trim("{$patientUser?->name} {$patientUser?->last_name}") ?: 'المريض';

            $title = 'وصول الطبيب لمنزل المريض 📍';
            $body  = "وصل د. {$doctorName} الآن إلى منزل المريض {$patientName} وبدأ بالزيارة المنزلية.";

            $data  = [
                'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                'action'        => 'OPEN_HOME_VISIT_DETAILS',
                'home_visit_id' => (string) $visit->id,
                'clinic_id'     => (string) $visit->clinic_id,
                'status'        => 'arrived',
            ];

            $fcmService->sendToUser($receptionUser->id, $title, $body, $data);
        }
    }
    public function completeVisit($id)
    {
        $doctorProfile = Auth::user()->doctorProfile;

        if (!$doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'حساب المستخدم غير مرتبط بملف طبيب.'
            ], 403);
        }

        $visit = HomeVisit::where('id', $id)
            ->where('doctor_id', $doctorProfile->id)
            ->where('status', 'arrived')
            ->firstOrFail();

        $visit->update([
            'status' => 'completed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إتمام الزيارة بنجاح وإغلاق الملف'
        ], 200);
    }
    public function getVisitHistory()
    {
        $doctorProfile = Auth::user()->doctorProfile;

        if (!$doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'حساب المستخدم غير مرتبط بملف طبيب.'
            ], 403);
        }

        $history = HomeVisit::where('doctor_id', $doctorProfile->id)
            ->where('status', 'completed')
            ->with(['patient.user'])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب أرشيف الزيارات المكتملة بنجاح',
            'data'    => $history
        ], 200);
    }
}
