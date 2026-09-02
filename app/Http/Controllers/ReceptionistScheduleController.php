<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Appointment;
use App\Models\HomeVisit;
use App\Services\DoctorScheduleService;
use App\Services\ReceptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ], 422);
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
                    'last_name'      => $doctor->user->last_name,
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
    public function getClinicAppointments(Request $request)
    {
        $user = Auth::user();

        // 1. التأكد من وجود بروفايل الريسبشن والعيادة المرتبطة به
        $reception = $user->reception;

        if (!$reception || !$reception->clinic_id) {
            return response()->json([
                'status'  => false,
                'message' => 'لم يتم العثور على عيادة مرتبطة بحساب موظف الاستقبال هذا.'
            ], 404);
        }

        $clinicId = $reception->clinic_id;

        // 2. بناء الـ Query مع الـ Eager Loading
        $query = Appointment::query()
            ->where('clinic_id', $clinicId)
            ->with([
                'patient.user:id,name,last_name,phone',
                'doctor.user:id,name,last_name'
            ]);

        // 3. الفلاتر الاختيارية (Filtering)

        // أ. الفلترة حسب التاريخ:
        if ($request->filled('date')) {
            // تاريخ محدد
            $query->whereDate('appointment_date', $request->date);
        } elseif ($request->filled('from_date') && $request->filled('to_date')) {
            // نطاق تواريخ محدد
            $query->whereBetween('appointment_date', [$request->from_date, $request->to_date]);
        } else {
            // الافتراضي: من اليوم وطالع (Upcoming Appointments)
            // يمكنك تحديد عدد أيام معينة، مثلاً القادمة خلال 30 يوم، أو تركها مفتوحة
            $query->whereDate('appointment_date', '>=', today());
        }

        // ب. الفلترة حسب الطبيب
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // ج. الفلترة حسب حالة الموعد
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // د. الفلترة حسب البحث باسم المريض أو رقم هاتفه
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 4. جلب المواعيد مرتبة بالتاريخ والوقت
        $appointments = $query->orderBy('appointment_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // 5. تجميع المواعيد حسب تاريخ اليوم (Group by Date)
        $groupedAppointments = $appointments->groupBy(function ($appointment) {
            // تحويل التاريخ إلى صيغة YYYY-MM-DD كمفتاح للتجميع
            return \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d');
        });

        return response()->json([
            'status'  => true,
            'message' => 'تم جلب مواعيد العيادة القادمة بنجاح',
            'data'    => $groupedAppointments
        ], 200);
    }
    public function shiftAppointments(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'target_date' => 'required|date|after_or_equal:today|different:from_date',
            'doctor_id' => 'nullable|exists:doctors,id', // اختياري: لتأجيل مواعيد طبيب محدد فقط، أو تركها فارغة لتأجيل كل العيادة
        ]);
        $user = Auth::user();
        $reception = $user->reception;

        if (!$reception || !$reception->clinic_id) {
            return response()->json([
                'status' => false,
                'message' => 'حساب الاستقبال غير مرتبط بعيادة.'
            ], 404);
        }

        $clinicId = $reception->clinic_id;
        $fromDate = $request->from_date;
        $targetDate = $request->target_date;

        // 2. جلب المواعيد القابلة للتأجيل فقط (scheduled)
        $query = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $fromDate)
            ->where('status', 'scheduled');

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $appointments = $query->get();

        if ($appointments->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'لا توجد مواعيد مجدولة في هذا اليوم لنقلها.'
            ], 404);
        }

        // 3. فحص التضارب في اليوم الجديد قبل إجراء التعديل
        $targetDayOfWeek = strtolower(Carbon::parse($targetDate)->format('l')); // e.g. "monday"

        foreach ($appointments as $appointment) {
            // أ. التأكد من عدم وجود موعد آخر لنفس الطبيب بنفس الوقت في اليوم الجديد
            $hasConflict = Appointment::where('doctor_id', $appointment->doctor_id)
                ->whereDate('appointment_date', $targetDate)
                ->where('status', 'scheduled')
                ->where(function ($q) use ($appointment) {
                    $q->whereBetween('start_time', [$appointment->start_time, $appointment->end_time])
                        ->orWhereBetween('end_time', [$appointment->start_time, $appointment->end_time]);
                })->exists();

            if ($hasConflict) {
                return response()->json([
                    'status' => false,
                    'message' => "تعذر التأجيل: يوجد تعارض في المواعيد للطبيب (ID: {$appointment->doctor_id}) في الوقت {$appointment->start_time} بتاريخ {$targetDate}."
                ], 422);
            }
        }

        // 4. تنفيذ التأجيل داخل Transaction لضمان سلامة البيانات
        DB::transaction(function () use ($appointments, $targetDate) {
            foreach ($appointments as $appointment) {
                $appointment->update([
                    'appointment_date' => $targetDate,
                    // يمكنك إبقاء الحالة 'scheduled' أو إضافة ملاحظة بسيطة
                ]);

                // هنا يمكنك إرسال إشعار للمريض بالتاريخ الجديد
                // Notification::send($appointment->patient->user, new AppointmentRescheduledNotification($appointment));
            }
        });

        return response()->json([
            'status' => true,
            'message' => "تم تأجيل {$appointments->count()} موعد بنجاح إلى تاريخ {$targetDate}."
        ], 200);
    }
    public function updateStatusNoShow(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        $reception = $user->reception;

        // 1. التأكد من أن الموعد يتبع لنفس عيادة الريسبشن
        if (!$reception || $appointment->clinic_id !== $reception->clinic_id) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بتعديل مواعيد هذه العيادة.'
            ], 403);
        }

        // 2. التحقق من المدخلات
        $request->validate([
            'status' => ['required', Rule::in(['cancelled', 'no_show', 'arrived', 'completed'])],
            'notes'  => 'nullable|string|max:500',
        ]);

        // 3. تحديث البيانات
        $appointment->update([
            'status' => $request->status,
            'notes'  => $request->filled('notes') ? $appointment->notes . "\n" . $request->notes : $appointment->notes
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث حالة الموعد بنجاح.',
            'data'    => $appointment->fresh(['patient.user:id,name,last_name'])
        ], 200);
    }
}
