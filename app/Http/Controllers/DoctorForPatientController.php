<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Attachment;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\Patient;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DoctorForPatientController extends Controller
{
    public function getPatientFullProfileById($id)
    {
        $patient = Patient::with([
            'user',
            'medicalRecords.doctor.user',
            'medicalRecords.appointment.prescription.items',
            // جلب موعد اليوم الفعلي (إن وجد)
            'appointments' => function ($query) {
                $query->whereIn('status', ['pending', 'scheduled', 'arrived'])
                    ->whereDate('appointment_date', Carbon::today())
                    ->latest();
            }
        ])->find($id);
        if (!$patient) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، المريض غير مسجل في النظام أو الرقم غير صحيح.'
            ], 404);
        }
        $attachments = Attachment::with('appointment.doctor.user')
            ->where('patient_id', $patient->id)
            ->latest()
            ->get();

        $currentAppt = $patient->appointments->first();
        $data = [
            'personal_info' => [
                'id'         => $patient->id,
                'name'       => ($patient->user->name ?? 'غير متوفر') . ' ' . ($patient->user->last_name ?? ''),
                'birth_date' => $patient->birth_date,
                'age'        => $patient->birth_date ? Carbon::parse($patient->birth_date)->age : null,
                'gender'     => $patient->gender,
                'blood_type' => $patient->blood_type,
                'weight'     => $patient->weight,
                'taller'     => $patient->taller,
            ],
            'medical_background' => [
                'allergies'        => $patient->allergies,
                'chronic_diseases' => $patient->chronic_diseases,
                'hereditary'       => $patient->hereditary,
            ],

            'current_appointment' => $currentAppt ? [
                'appointment_id' => $currentAppt->id,
                'status'         => $currentAppt->status,
                'start_time'     => $currentAppt->start_time,
            ] : null,

            'medical_history' => $patient->medicalRecords->map(function ($record) {
                return [
                    'record_id'       => $record->id,
                    'date'            => $record->created_at->format('Y-m-d'),
                    'doctor_name'     => ($record->doctor?->user?->name ?? 'غير متوفر') . ' ' . ($record->doctor?->user?->last_name ?? ''),
                    'diagnosis'       => $record->diagnosis,
                    'chief_complaint' => $record->chief_complaint,
                    'notes'           => $record->notes,

                    'prescription' => $record->appointment?->prescription ? [
                        'instructions' => $record->appointment->prescription->instructions,
                        'medicines'    => $record->appointment->prescription->items->map(function ($item) {
                            return [
                                'name'      => $item->medicine_name,
                                'dosage'    => $item->dosage,
                                'frequency' => $item->frequency,
                                'duration'  => $item->duration,
                            ];
                        })
                    ] : null,
                ];
            }),

            'attachments' => $attachments->map(function ($file) {
                return [
                    'attachment_id' => $file->id,
                    'title'         => $file->title ?? 'ملف بدون عنوان',
                    'file_type'     => $file->file_type,
                    'file_url'      => Storage::disk($file->disk)->url($file->file_path),
                    'upload_date'   => $file->created_at->format('Y-m-d H:i'),
                    'appointment_info' => $file->appointment ? [
                        'appointment_id' => $file->appointment->id,
                        'date'           => $file->appointment->appointment_date,
                        'doctor_name'    => ($file->appointment->doctor?->user?->name ?? 'غير متوفر') . ' ' . ($file->appointment->doctor?->user?->last_name ?? ''),
                    ] : null,
                ];
            }),

            'analyses' => $attachments->map(function ($attachment) {
                return [
                    'id'             => $attachment->id,
                    'appointment_id' => $attachment->appointment_id,
                    'title'          => $attachment->title,
                    'file_type'      => $attachment->file_type,
                    'file_url'       => Storage::disk($attachment->disk)->url($attachment->file_path),
                    'date'           => $attachment->created_at->format('Y-m-d'),
                    'requested_by'   => ($attachment->appointment->doctor->user->name ?? 'غير متوفر') . ' ' . ($attachment->appointment->doctor->user->last_name ?? ''),
                ];
            }),
        ];

        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب ملف المريض الشامل بنجاح باستخدام المعرف (ID).',
            'data'    => $data
        ], 200);
    }
    public function getPatientAnalyses($patient_id)
    {
        $patient = Patient::findOrFail($patient_id);
        $analyses = Attachment::whereHas('appointment', function ($query) use ($patient_id) {
            $query->where('patient_id', $patient_id);
        })
            ->with(['appointment.doctor.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        // تشكيل البيانات (Formatting)
        $data = $analyses->map(function ($attachment) {
            return [
                'id'             => $attachment->id,
                'appointment_id' => $attachment->appointment_id,
                'title'          => $attachment->title,
                'file_type'      => $attachment->file_type,
                'file_url'       => asset('storage/' . $attachment->file_path),
                'date'           => $attachment->created_at->format('Y-m-d'),
                'requested_by'   => $attachment->appointment->doctor->user->name ?? 'غير متوفر',
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب تحاليل المريض بنجاح',
            'data'    => $data
        ]);
    }
    public function getTodayAppointments()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بالوصول. التوكن غير صالح أو مفقود.'
            ], 401);
        }

        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return response()->json([
                'status' => false,
                'message' => 'هذا الحساب غير مسجل كطبيب.'
            ], 403);
        }

        $todayAppointments = Appointment::with(['patient.user']) // استخدمت patient.user تحسباً لكون الاسم في جدول المستخدمين
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', Carbon::today())
            ->get();

        $totalPatientsCount = $todayAppointments->count();

        $allowedStatuses = ['scheduled', 'arrived', 'completed', 'no_show'];
        $groupedData = [];
        $statusCounts = [];

        foreach ($allowedStatuses as $status) {
            $appointmentsByStatus = $todayAppointments->where('status', $status);
            $statusCounts[$status] = $appointmentsByStatus->count();
            $groupedData[$status] = $appointmentsByStatus->map(function ($appointment) {
                return [
                    'appointment_id'   => $appointment->id,
                    'patient_id'       => $appointment->patient_id,
                    'full_name' => ($appointment->patient?->user?->name ?? '') . ' ' . ($appointment->patient?->user?->last_name ?? ''),
                    'appointment_date' => $appointment->appointment_date,
                    'start_time'       => $appointment->start_time,
                    'end_time'         => $appointment->end_time,
                    'status'           => $appointment->status,
                    'notes' => $appointment->notes,

                ];
            })->values(); // إعادة تعيين الفهارس (indexes) لتكون متسلسلة في الـ JSON
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب وتصنيف مواعيد اليوم بنجاح',
            'statistics' => [
                'total_today_patients' => $totalPatientsCount,
                'counts_by_status' => $statusCounts
            ],
            'data' => $groupedData
        ], 200);
    }
    public function searchPatients(Request $request)
    {
        $search = $request->input('search');

        if (blank($search)) {
            return response()->json([
                'status' => true,
                'action_type' => 'show_list',
                'data' => []
            ]);
        }

        if (is_numeric($search)) {
            $patient = Patient::with('user')->find($search);

            if ($patient) {
                return response()->json([
                    'status' => true,
                    'action_type' => 'direct_open',
                    'data' => [$patient]
                ]);
            }
        }

        $patients = Patient::whereHas('user', function ($query) use ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        })
            ->with('user')
            ->get();

        return response()->json([
            'status' => true,
            'action_type' => 'show_list',
            'data' => $patients
        ]);
    }
    public function getPatientAttachments($id)
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'status'  => 'error',
                'message' => 'المريض غير موجود في النظام.'
            ], 404);
        }

        $attachments = Attachment::with('appointment.doctor.user')
            ->where('patient_id', $id)
            ->latest()
            ->get();

        $data = $attachments->map(function ($file) {
            return [
                'attachment_id' => $file->id,
                'title'         => $file->title ?? 'ملف بدون عنوان',
                'file_type'     => $file->file_type, // مثلا: image/png, application/pdf
                'file_url'      => Storage::disk($file->disk)->url($file->file_path),
                'upload_date'   => $file->created_at->format('Y-m-d H:i'),
                'appointment_info' => $file->appointment ? [
                    'appointment_id' => $file->appointment->id,
                    'date'           => $file->appointment->appointment_date,
                    'doctor_name'    => $file->appointment->doctor?->user?->name ?? 'غير متوفر',
                ] : null,
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب المرفقات الطبية بنجاح.',
            'data'    => $data
        ], 200);
    }
    public function getCurrentQueue()
    {
        $today = Carbon::today()->toDateString();
        $doctor = Doctor::where('user_id', Auth::id())->first();
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المستخدم ليس طبيباً مسجلاً.'
            ], 403);
        }
        $doctorId = $doctor->id;

        $currentPatient = Appointment::with('patient')
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $today)
            ->where('status', 'arrived')
            ->orderBy('start_time', 'asc')
            ->first();

        $nextPatient = Appointment::with('patient')
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $today)
            ->where('status', 'scheduled')
            ->orderBy('start_time', 'asc')
            ->first();
        return response()->json([
            'success' => true,
            'data' => [
                'current_patient' => $currentPatient ? [
                    'appointment_id' => $currentPatient->id,
                    'patient_id'     => $currentPatient->patient->id,
                    'full_name' => ($currentPatient->patient?->user?->name ?? '') . ' ' . ($currentPatient->patient?->user?->last_name ?? ''),
                    'start_time'     => $currentPatient->start_time,
                    'status'     => $currentPatient->status,
                    'notes'     => $currentPatient->notes,

                ] : null,
                'next_patient' => $nextPatient ? [
                    'appointment_id' => $nextPatient->id,
                    'patient_id'     => $nextPatient->patient->id,
                    'full_name' => ($nextPatient->patient?->user?->name ?? '') . ' ' . ($nextPatient->patient?->user?->last_name ?? ''),
                    'start_time'     => $nextPatient->start_time,
                    'status'     => $nextPatient->status,
                    'notes'     => $nextPatient->notes,

                ] : null,
            ]
        ]);
    }
    public function getPatientFullProfile($qr_token)
    {
        $patient = Patient::with([
            'user',
            'medicalRecords.doctor.user',
            'medicalRecords.appointment.prescription.items',
            'appointments' => function ($query) {
                $query->whereIn('status', ['pending', 'scheduled', 'arrived'])
                    ->whereDate('appointment_date', Carbon::today())
                    ->latest();
            }
        ])->where('qr_token', $qr_token)->first(); // هنا تم التعديل للبحث عن طريق الـ QR

        if (!$patient) {
            return response()->json([
                'status'  => 'error',
                'message' => 'رمز الـ QR غير صالح أو المريض غير مسجل في النظام.'
            ], 404);
        }
        $attachments = Attachment::with('appointment.doctor.user')
            ->where('patient_id', $patient->id)
            ->latest()
            ->get();

        $currentAppt = $patient->appointments->first();

        $data = [
            'personal_info' => [
                'id'         => $patient->id,
                'name'       => $patient->user->name ?? 'غير متوفر',
                'birth_date' => $patient->birth_date,
                'age'        => $patient->birth_date ? Carbon::parse($patient->birth_date)->age : null,
                'gender'     => $patient->gender,
                'blood_type' => $patient->blood_type,
                'weight'     => $patient->weight,
                'taller'     => $patient->taller,
            ],
            'medical_background' => [
                'allergies'        => $patient->allergies,
                'chronic_diseases' => $patient->chronic_diseases,
                'hereditary'       => $patient->hereditary,
            ],

            'current_appointment' => $currentAppt ? [
                'appointment_id' => $currentAppt->id,
                'status'         => $currentAppt->status,
                'start_time'     => $currentAppt->start_time,
            ] : null,

            'medical_history' => $patient->medicalRecords->map(function ($record) {
                return [
                    'record_id'       => $record->id,
                    'date'            => $record->created_at->format('Y-m-d'),
                    'doctor_name'     => $record->doctor?->user?->name ?? 'غير متوفر',
                    'diagnosis'       => $record->diagnosis,
                    'chief_complaint' => $record->chief_complaint,
                    'notes'           => $record->notes,

                    'prescription' => $record->appointment?->prescription ? [
                        'instructions' => $record->appointment->prescription->instructions,
                        'medicines'    => $record->appointment->prescription->items->map(function ($item) {
                            return [
                                'name'      => $item->medicine_name,
                                'dosage'    => $item->dosage,
                                'frequency' => $item->frequency,
                                'duration'  => $item->duration,
                            ];
                        })
                    ] : null,
                ];
            }),

            'attachments' => $attachments->map(function ($file) {
                return [
                    'attachment_id' => $file->id,
                    'title'         => $file->title ?? 'ملف بدون عنوان',
                    'file_type'     => $file->file_type,
                    'file_url'      => Storage::disk($file->disk)->url($file->file_path),
                    'upload_date'   => $file->created_at->format('Y-m-d H:i'),
                    'appointment_info' => $file->appointment ? [
                        'appointment_id' => $file->appointment->id,
                        'date'           => $file->appointment->appointment_date,
                        'doctor_name'    => $file->appointment->doctor?->user?->name ?? 'غير متوفر',
                    ] : null,
                ];
            }),

            'analyses' => $attachments->map(function ($attachment) {
                return [
                    'id'             => $attachment->id,
                    'appointment_id' => $attachment->appointment_id,
                    'title'          => $attachment->title,
                    'file_type'      => $attachment->file_type,
                    'file_url'       => Storage::disk($attachment->disk)->url($attachment->file_path),
                    'date'           => $attachment->created_at->format('Y-m-d'),
                    'requested_by'   => $attachment->appointment->doctor->user->name ?? 'غير متوفر',
                ];
            }),
        ];

        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب ملف المريض الشامل بنجاح باستخدام رمز الـ QR.',
            'data'    => $data
        ], 200);
    }
    public function getProfile()
    {
        $user = Auth::user();
        $doctorProfile = $user->doctorProfile;

        if (!$doctorProfile) {
            return response()->json([
                'status'  => false,
                'message' => 'لم يتم العثور على ملف شخصي لهذا الطبيب.'
            ], 404);
        }
        $doctorProfile->load([
            'user:id,name,last_name,email,phone',
            'speciality:id,specialization_name',
            'clinic:id,clinic_name,open_time,close_time'
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم جلب الملف الشخصي للطبيب بنجاح.',
            'data'    => $doctorProfile
        ], 200);
    }
}
