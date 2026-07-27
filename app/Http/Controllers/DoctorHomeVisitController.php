<?php

namespace App\Http\Controllers;

use App\Models\HomeVisit;
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

    /**
     * قبول الزيارة من قبل الطبيب
     */
public function acceptVisit($id)
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

    if ($visit->status !== 'assigned') {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن قبول هذه الزيارة لأن حالتها الحالية هي: ' . $visit->status
        ], 422);
    }
    $visit->update([
        'status' => 'accepted'
    ]);

    return response()->json([
        'success' => true,
        'message' => 'تم قبول الزيارة بنجاح، يمكنك الآن بدء التوجه عند الاستعداد'
    ], 200);
}

    /**
     * اعتذار/رفض الزيارة من قبل الطبيب (لتقوم الإدارة أو الاستقبال بتعيين طبيب آخر)
     */
    public function rejectVisit(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $doctorProfile = Auth::user()->doctorProfile;

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

        $visit->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            // تفريغ الطبيب ليتمكن الاستقبال من إعادة إسنادها لطبيب آخر
            'doctor_id'        => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الاعتذار عن الزيارة وتحديث الحالة للاستقبال'
        ], 200);
    }

    /**
     * بدء التوجه إلى منزل المريض
     */
    public function startVisit($id)
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
            ->where('status', 'accepted')
            ->firstOrFail();

        $visit->update([
            'status' => 'on_the_way'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير حالة الزيارة إلى: في الطريق إلى المريض'
        ], 200);
    }

    /**
     * تأكيد الوصول لمنزل المريض
     */
    public function arriveVisit($id)
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
            ->where('status', 'on_the_way')
            ->firstOrFail();

        $visit->update([
            'status' => 'arrived'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد الوصول لمنزل المريض، يمكنك البدء بالفحص وتمرير السجل الطبي'
        ], 200);
    }

    /**
     * إتمام وإنهاء الزيارة بالكامل
     */
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

    /**
     * أرشيف الزيارة المنتهية للطبيب
     */
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
