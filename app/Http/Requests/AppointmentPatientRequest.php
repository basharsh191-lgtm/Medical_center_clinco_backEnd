<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\AppointmentService;
use Illuminate\Support\Facades\Auth;

class AppointmentPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // التأكد من أن المستخدم مسجل دخول
    }

    public function rules(): array
    {
        return [
            'doctor_id'        => 'required|exists:doctors,id',
            'clinic_id'        => 'required|exists:clinics,id',
            'appointment_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time'       => 'required|date_format:H:i',
            'end_time'         => 'required|date_format:H:i',
            'notes'            => 'nullable|string|max:500',
        ];
    }

    /**
     * الـ Hook المخصص للفحص بعد نجاح قواعد الـ Validation الأساسية
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // جلب الـ Service عن طريق الـ Service Container تلقائياً
            $appointmentService = app(AppointmentService::class);

            // التحقق من توافر الموعد
            $isAvailable = $appointmentService->isSlotAvailable(
                $this->doctor_id,
                $this->appointment_date,
                $this->start_time,
                $this->end_time
            );

            if (!$isAvailable) {
                $validator->errors()->add('appointment_date', 'عذراً، هذا الموعد غير متاح أو تم حجزه مؤخراً.');
            }
        });
    }

    /**
     * دمج الـ patient_id تلقائياً مع البيانات التي تم التحقق منها
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // appointments.patient_id مفتاح أجنبي إلى patients.id وليس users.id
        // لذلك Auth::id() خطأ هنا — يجب المرور عبر سجل المريض
        $patientId = Auth::user()?->patient?->id;

        abort_if(!$patientId, response()->json([
            'success' => false,
            'message' => 'هذا الحساب غير معرف كمريض في النظام.',
        ], 403));

        $validated['patient_id'] = $patientId;

        return $validated;
    }
}
