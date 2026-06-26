<?php

namespace App\Http\Controllers;

use App\Models\HomeVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceptionHomeVisitController extends Controller
{
    public function getClinicHomeVisits(Request $request)
    {
        // جلب رقم عيادة موظف الاستقبال الحالي
        $clinicId = Auth::user()->reception->clinic_id;

        $homeVisits = HomeVisit::where('clinic_id', $clinicId)
            ->with(['patient.user', 'doctor'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب طلبات الرعاية المنزلية للعيادة بنجاح',
            'data'    => [
                'visits' => $homeVisits->items(), // مصفوفة الزيارات فقط بدون تعقيدات
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
    // قَبول الطلب وتعيين الطبيب
    public function approveAndAssignDoctor(Request $request, $id)
    {
        $clinicId = Auth::user()->reception->clinic_id;
        HomeVisit::where('clinic_id', $clinicId);
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);
        $visit = HomeVisit::findOrFail($id);
        $visit->update([
            'doctor_id' => $request->doctor_id,
            'status'    => 'assigned'
        ]);
        return response()->json([
            'success' => true,
            'message' => 'تم قبول طلب الرعاية المنزلية وتعيين الطبيب بنجاح'
        ], 200);
    }
    public function rejectVisit(Request $request, $id)
    {
        $clinicId = Auth::user()->reception->clinic_id;
        HomeVisit::where('clinic_id', $clinicId);
        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $visit = HomeVisit::findOrFail($id);

        $visit->update([
            'status' => 'cancelled',
            'rejection_reason' => $request->rejection_reason
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الطلب بنجاح وتوثيق السبب'
        ], 200);
    }
}
