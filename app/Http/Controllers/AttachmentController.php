<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Appointment;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
// public function store(Request $request)
//     {
//         // 1. جلب رقم المريض الحالي من التوكن (أمان عالي)
//         $currentPatientId = Auth::user()->patient->id;

//         // 2. الفاليديشن: لاحظ أن appointment_id صار nullable (اختياري)
//         $request->validate([
//             'appointment_id' => 'nullable|exists:appointments,id',
//             'file'           => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
//             'title'          => 'nullable|string|max:255',
//         ]);

//         // 3. التحقق الأمني: نفحصه فقط إذا المريض قرر يربط الملف بموعد
//         if ($request->filled('appointment_id')) {
//             $appointment = Appointment::findOrFail($request->appointment_id);

//             if ($appointment->patient_id !== $currentPatientId) {
//                 return response()->json(['message' => 'غير مصرح لك بإضافة مرفقات لهذا الموعد'], 403);
//             }
//         }

//         // 4. معالجة الملف المرفوع
//         if ($request->hasFile('file')) {
//             $file = $request->file('file');

//             // تخزين الملف
//             $path = $file->store('attachments', 's3');
//             // 5. حفظ بيانات الملف في جدول المرفقات
//             $attachment = Attachment::create([
//                 'patient_id'     => $currentPatientId, // تم إضافة رقم المريض إجبارياً
//                 'appointment_id' => $request->appointment_id, // سيكون null إذا لم يتم إرساله
//                 'title'          => $request->title ?? 'مرفق بدون عنوان',
//                 'file_path'      => $path,
//                 'file_type'      => $file->getClientOriginalExtension(),
//             ]);

//             // 6. إرجاع استجابة للتطبيق
//             return response()->json([
//                 'status'   => 'success',
//                 'message'  => 'تم رفع المرفق بنجاح',
//                 'data'     => $attachment,
//                 'file_url' => Storage::disk('s3')->url($path)
//                 ], 201);
//         }

//         return response()->json([
//             'status'  => 'error',
//             'message' => 'لم يتم إرسال أي ملف'
//         ], 400);
//     }
public function storeAttachments(StoreAttachmentRequest $request){
        $currentPatientId = Auth::user()->patient->id;
        $request->validated();
        // تحديد السيرفر (إذا الموبايل ما بعت شي، منستخدم public كافتراضي)
        $diskName = $request->storage_disk ?? 'public';
        if ($request->filled('appointment_id')) {
            $appointment = Appointment::findOrFail($request->appointment_id);
            if ($appointment->patient_id !== $currentPatientId) {
                return response()->json(['message' => 'غير مصرح لك بإضافة مرفقات لهذا الموعد'], 403);
            }
        }
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            // التخزين الديناميكي: منمرر اسم السيرفر كمتغير للدالة store
            $path = $file->store('attachments', $diskName);
            $attachment = Attachment::create([
                'patient_id'     => $currentPatientId,
                'appointment_id' => $request->appointment_id,
                'title'          => $request->title ?? 'مرفق بدون عنوان',
                'file_path'      => $path,
                'file_type'      => $file->getClientOriginalExtension(),
                'disk'           => $diskName, // حفظنا اسم السيرفر بالداتا بيز!
            ]);
            // توليد الرابط الديناميكي حسب السيرفر اللي انحفظ عليه
            $fileUrl = Storage::disk($diskName)->url($path);
            return response()->json([
                'status'   => 'success',
                'message'  => 'تم رفع المرفق بنجاح',
                'data'     => $attachment,
                'file_url' => $fileUrl
            ], 201);
        }

        return response()->json(['status' => 'error', 'message' => 'لم يتم إرسال أي ملف'], 400);
    }
    public function getMyAttachments()
    {
        $patientId = Auth::user()->patient->id;

        $attachments = Attachment::where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب المرفقات بنجاح',
            'data'    => AttachmentResource::collection($attachments)
        ]);
    }
}
