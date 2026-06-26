<?php

namespace App\Http\Controllers;

use App\Models\HomeVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorHomeVisitController extends Controller
{
//     public function getDoctorAssignedVisits(Request $request)
// {
//     // جلب الـ doctor_id الخاص بالطبيب الحالي (تأكد من إعدادها حسب جدول الـ users أو الـ relationship لديك)
//     $doctorId = Auth::user()->doctors->id ?? null;

//     if (!$doctorId) {
//         return response()->json([
//             'success' => false,
//             'message' => 'هذا الحساب غير معرف كطبيب في النظام.',
//         ], 403);
//     }

//     $visits = HomeVisit::where('doctor_id', $doctorId)
//         ->where('status', 'assigned') // جلب الطلبات النشطة فقط
//         ->with(['patient.user'])
//         ->orderBy('visit_date', 'asc')
//         ->paginate(15);

//     $customVisits = collect($visits->items())->map(function ($visit) {
//         return [
//             'id'         => $visit->id,
//             'visit_date' => $visit->visit_date,
//             'start_time' => $visit->start_time,
//             'end_time'   => $visit->end_time,
//             'status'     => $visit->status,
//             'patient'    => $visit->patient && $visit->patient->user ? [
//                 'id'    => $visit->patient->user->id,
//                 'name'  => $visit->patient->user->name,
//                 'phone' => $visit->patient->user->phone,
//             ] : null,
//         ];
//     });

//     return response()->json([
//         'success' => true,
//         'message' => 'تم جلب الزيارات المنزلية الموكلة إليك بنجاح',
//         'data'    => [
//             'visits'     => $customVisits,
//             'pagination' => [
//                 'current_page' => $visits->currentPage(),
//                 'last_page'    => $visits->lastPage(),
//                 'total'        => $visits->total(),
//             ]
//         ]
//     ], 200);
// }
// public function completeVisit(Request $request, $id)
// {
//     $visit = HomeVisit::findOrFail($id);

//     // التأكد أن الطبيب الحالي هو نفسه الموكل بالزيارة حمايةً للبيانات
//     $doctorId = Auth::user()->doctor->id ?? null;
//     if ($visit->doctor_id !== $doctorId) {
//         return response()->json([
//             'success' => false,
//             'message' => 'غير مسموح لك بتعديل حالة هذه الزيارة.'
//         ], 403);
//     }

//     $visit->update([
//         'status' => 'completed'
//     ]);

//     return response()->json([
//         'success' => true,
//         'message' => 'تم إنهاء الزيارة المنزلية وإغلاق الطلب بنجاح'
//     ], 200);
// }
}
