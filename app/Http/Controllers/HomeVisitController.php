<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHomeVisitRequest;
use App\Http\Requests\UpdateHomeVisitRequest;
use App\Models\HomeVisit;
use App\Models\patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeVisitController extends Controller
{
public function requestHomeVisit(StoreHomeVisitRequest $request)
    {
// 1. جلب سجل المريض المرتبط بالمستخدم المسجل حالياً
    $patient = patient::where('user_id', Auth::id())->first();

    if (!$patient) {
        return response()->json([
            'success' => false,
            'message' => 'لم يتم العثور على بيانات المريض الخاصة بهذا الحساب'
        ], 404);
    }

    // 2. استخدام العلاقة الموجودة داخل موديل patient لإنشاء الطلب
    $homeVisit = $patient->homeVisits()->create($request->validated());

    return response()->json([
        'success' => true,
        'message' => 'تم إرسال طلب الرعاية المنزلية بنجاح',
        'data'    => $homeVisit
    ], 201);
}
public function updateHomeVisit(UpdateHomeVisitRequest $request, $id)
{
    // 1. التحقق من أن المستخدم مسجل كمريض
    $patientId = Auth::user()->patient->id ?? null;

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

    return response()->json([
        'success' => true,
        'message' => 'تم تعديل طلب الرعاية المنزلية بنجاح',
        'data'    => $homeVisit
    ], 200);
}
public function cancelHomeVisit($id)
{
    // 1. التحقق من أن المستخدم الحالي مسجل كمريض
    $patientId = Auth::user()->patient->id ?? null;

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

    return response()->json([
        'success' => true,
        'message' => 'تم إلغاء طلب الرعاية المنزلية بنجاح',
        'data'    => $homeVisit
    ], 200);
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
