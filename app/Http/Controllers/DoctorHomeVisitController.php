<?php

namespace App\Http\Controllers;

use App\Models\HomeVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorHomeVisitController extends Controller
{
    /**
     * 1. جلب الزيارات المنزلية الحالية الخاصة بالطبيب
     */
    public function getMyHomeVisits(Request $request)
    {
        // 1. جلب كائن المستخدم الحالي
        $user = Auth::user();

        // 2. التحقق من وجود المستخدم وامتلاكه لعلاقة doctorProfile
        if (!$user || !$user->doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المستخدم ليس مسجلاً كطبيب في النظام أو لم يسجل دخوله بعد.',
                'data'    => [
                    'visits' => [],
                    'pagination' => null
                ]
            ], 403);
        }

        // 3. جلب معرف الطبيب الصحيح من العلاقة (doctorProfile)
        $doctorId = $user->doctorProfile->id;

        // 4. جلب الزيارات بناءً على الـ doctor_id الرقمي الصحيح
        $visits = HomeVisit::where('doctor_id', $doctorId)
            ->whereIn('status', ['assigned', 'on_the_way'])
            ->with(['patient.user']) // جلب بيانات المريض والمستخدم المرتبط به
            ->orderBy('visit_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الزيارات المنزلية الحالية بنجاح',
            'data'    => [
                'visits' => $visits->items(),
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

    /**
     * 2. بدء التوجه للمريض (تغيير الحالة إلى on_the_way)
     */
    public function startVisit($id)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم لديه ملف طبيب مرتبط به
        if (!$user || !$user->doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المستخدم ليس مسجلاً كطبيب في النظام.'
            ], 403);
        }

        $doctorId = $user->doctorProfile->id;

        // البحث عن الزيارة الخاصة بالدكتور وتكون حالتها assigned فقط (ليبدأها)
        $visit = HomeVisit::where('doctor_id', $doctorId)
            ->where('status', 'assigned')
            ->findOrFail($id);

        // تحديث الحالة إلى في الطريق
        $visit->update([
            'status' => 'on_the_way'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم بدء الرحلة والتوجه إلى موقع المريض بنجاح',
            'data'    => $visit
        ], 200);
    }

    /**
     * 3. سجل الزيارات المنتهية (المكتملة والملغاة)
     */
    public function getVisitHistory(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم لديه ملف طبيب مرتبط به
        if (!$user || !$user->doctorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المستخدم ليس مسجلاً كطبيب في النظام.',
                'data'    => ['visits' => []]
            ], 403);
        }

        $doctorId = $user->doctorProfile->id;

        // جلب الزيارات المنتهية (سواء اكتملت أو ألغيت)
        $history = HomeVisit::where('doctor_id', $doctorId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->with(['patient.user'])
            ->orderBy('updated_at', 'desc') // الترتيب حسب تاريخ الإغلاق الأحدث
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب سجل الزيارات المنتهية بنجاح',
            'data'    => [
                'visits' => $history->items(),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'last_page'    => $history->lastPage(),
                    'per_page'     => $history->perPage(),
                    'total'        => $history->total(),
                    'has_more'     => $history->hasMorePages(),
                ]
            ]
        ], 200);
    }
}
