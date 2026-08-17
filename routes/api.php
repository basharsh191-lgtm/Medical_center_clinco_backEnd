<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ReceptionistScheduleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClincController;
use App\Http\Controllers\DoctorForPatientController;
use App\Http\Controllers\DoctorHomeVisitController;
use App\Http\Controllers\HomeCareLabOrderController;
use App\Http\Controllers\HomeVisitController;
use App\Http\Controllers\LabOrderController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReceptionHomeVisitController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

//Auth
    Route::post('/register',[AuthController::class,'register']);
    Route::post('/login',[AuthController::class,'login'])->middleware('throttle:5,1'); // تقييد محاولات تسجيل الدخول لمنع الهجمات
    Route::post('/doctorLogin',[AuthController::class,'doctorLogin'])->middleware('throttle:5,1'); // تقييد محاولات تسجيل الدخول لمنع الهجمات
    Route::post('logout',[AuthController::class,'logout'])->middleware('auth:sanctum');
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    Route::post('/verify_otp',[AuthController::class,'verifyOtp']);
    Route::post('/resendOtp',[AuthController::class,'resendOtp']);

//patient
Route::middleware(['auth:sanctum','role:patient'])->group(function () {
    Route::post('/storePatient', [PatientController::class, 'storePatient']);
    Route::put('patients/update', [PatientController::class, 'updatePatient']);
    Route::get('/showPatient',[PatientController::class,'showPatient']);
    Route::post('/appointmentStore',[PatientController::class,'appointmentStore']);
    Route::put('/appointmentUpdate/{appointment}',[PatientController::class,'appointmentUpdate']);
    Route::delete('/appointmentCancel/{appointment}',[PatientController::class,'appointmentCancel']);
    Route::post('/ratings/{doctorId}',[RatingController::class,'storeRating']);
    Route::get('/patient/getAppointments', [PatientController::class, 'patientAppointments']);
    Route::get('/appointments/{appointment}', [PatientController::class, 'showAppointment']);
    Route::get('/patient/my-labOrders', [LabOrderController::class, 'getMyLabOrders']);
    Route::post('/attachments', [AttachmentController::class, 'storeAttachments']);
    Route::get('/patient/my-medical-history', [MedicalRecordController::class, 'getMyMedicalHistory']);
    Route::get('/patient/my-attachments', [AttachmentController::class, 'getMyAttachments']);
    Route::get('/qr-token-for-my', [PatientController::class, 'getMyQrData']);
    Route::get('patient/favorites', [PatientController::class, 'getFavorites']);
    Route::post('doctors/{doctor}/favorite', [PatientController::class, 'toggleFavorite']);
    Route::get('patient/appointments/next', [PatientController::class, 'getNextAppointment']);

    //visit home
    Route::post('/request-home-visit', [HomeVisitController::class, 'requestHomeVisit']);
    Route::get('/patient/home-visits', [HomeVisitController::class, 'getPatientHomeVisits']);
    Route::put('/update-home-visit/{id}', [HomeVisitController::class, 'updateHomeVisit']);
    Route::delete('/cancel-home-visit/{id}', [HomeVisitController::class, 'cancelHomeVisit']);

});

    //عرض تقييمات الأطباء
    Route::get('/showAllRatingsDoctors', [RatingController::class, 'showAllRatingsDoctors']);
    Route::get('/showDoctorRatings/{id}', [RatingController::class, 'showDoctorRatings']);

    //show home page
    //استدعاء العيادة مع دكاترتها
    Route::get('/showClinic/{id}',[ClincController::class,'showClinic']);
    Route::get('/showClinicAll',[ClincController::class,'showClinicAll']);

    //عرض بروفايل دكتور معين
    Route::get('/showDoctor/{id}',[ClincController::class,'showDoctor']);
    //عرض اوقات المتاحة للطبيب
    Route::get('doctors/{doctor}/available-slots', [AppointmentController::class, 'getAvailableSlots']);
    Route::get('/clinics-with-doctors', [ClincController::class, 'getClinicsWithDoctors']);

//admain
Route::middleware(['auth:sanctum','role:super_admin'])->group(function () {
    //اضافة دكتور
    Route::post('/add/doctor/admin', [StaffController::class, 'storeDoctor']);
    //اضافة ريسبشن
    Route::post('/add/resiption/admin', [StaffController::class, 'storeReception']);
    //اضافة ممرض مستقبلا
    Route::post('/test-fcm', [StaffController::class, 'testFcmNotification']);
});

//reception
Route::middleware(['auth:sanctum','role:reception'])->group(function () {
    //اضافة جدول لدكتور معين داخل عيادة مخصصة
    Route::post('store/schedule/reception',[ReceptionistScheduleController::class,'storeSchedule']);
    //جلب كل الاطباء في العيادة التي يشتغل بها الريسبشن
    Route::get('get/doctors/reception',[ReceptionistScheduleController::class,'getMyClinicDoctors']);
    //تحويل حالة الموعد من  scheduled الىarrived
    Route::post('/receptionist/check-in', [ReceptionistScheduleController::class, 'checkInByPatientQR']);
    //جلب كل المواعيد الحالية للعيادة التي يشتغل بها الريسبشن
    Route::get('reception/appointments', [ReceptionistScheduleController::class, 'getClinicAppointments']);
    //نقل المواعيد لغير يوم
    Route::post('reception/shiftAppointments', [ReceptionistScheduleController::class, 'shiftAppointments']);
    //تحديث حالة الموعد no_show 
    Route::put('reception/appointments/{appointment}/no-show', [ReceptionistScheduleController::class, 'updateStatusNoShow']);
    //اضافة حساب مريض جديد 
    Route::post('reception/patients/new', [PatientController::class, 'storeAccountByReception']);
    //اضافة موعد walk-in
    Route::post('appointments/walk-in', [AppointmentController::class, 'storeWalkIn']);
//home visit
    Route::get('/show-home-visits', [ReceptionHomeVisitController::class, 'getClinicHomeVisits']);
    Route::post('/approve-home-visit/{id}', [ReceptionHomeVisitController::class, 'approveAndAssignDoctor']);
    Route::post('/reject-home-visit/{id}', [ReceptionHomeVisitController::class, 'rejectVisit']);
    Route::get('/rejected-home-visitsis-doctor', [ReceptionHomeVisitController::class, 'getRejectedVisits']);
    Route::get('/home-visits/{id}', [ReceptionHomeVisitController::class, 'getSingleHomeVisit']);
    Route::get('/home-visits/{visitId}/available-doctors', [ReceptionHomeVisitController::class, 'getAvailableDoctorsForHomeVisit']);
});
//doctor
Route::middleware(['auth:sanctum','role:doctor'])->group(function () {
    //تخزين السجل الطبي للمريض وإغلاق الموعد
    Route::post('/store-medical-record/{appointment}', [MedicalRecordController::class, 'storeMedicalRecord']);
    Route::put('/update-medical-record/{appointment}', [MedicalRecordController::class, 'updateMedicalRecord']);
    Route::delete('/delete-medical-record/{medicalRecord}', [MedicalRecordController::class, 'destroyMedicalRecord']);
    Route::get('/patients/{patient}/medical-history', [MedicalRecordController::class, 'getPatientHistory']);
    Route::get('/patients/{patient}/allergies', [MedicalRecordController::class, 'getPatientAllergies']);
    Route::put('/patients/{patient}/allergies', [MedicalRecordController::class, 'updatePatientAllergies']);

    Route::get('/patients/{id}/attachments', [DoctorForPatientController::class, 'getPatientAttachments']);
    Route::get('/patients/{patient_id}/analyses', [DoctorForPatientController::class, 'getPatientAnalyses']);
    Route::get('/doctor/today-appointments', [DoctorForPatientController::class, 'getTodayAppointments']);
    Route::post('/search_patient', [DoctorForPatientController::class, 'searchPatients']);

    Route::post('appointments/{appointment}/prescription', [PrescriptionController::class, 'storePrescription']);
    Route::get('prescriptions/{id}/show', [PrescriptionController::class, 'showPrescription']);
    Route::put('prescriptions/{id}/update', [PrescriptionController::class, 'updatePrescription']);
    Route::delete('prescriptions/{id}/delete', [PrescriptionController::class, 'destroyPrescription']);

    Route::post('appointments/{appointment}/lab-orders', [LabOrderController::class, 'storeLabOrderDoctor']);
    Route::get('show/{id}/lab-orders', [LabOrderController::class, 'editLabOrder']);
    Route::put('update/lab-orders/{id}', [LabOrderController::class, 'updateLabOrderDoctor']);
    Route::delete('appointments/lab-orders/{id}', [LabOrderController::class, 'cancelLabOrder']);

    Route::get('/doctor/current-queue', [DoctorForPatientController::class, 'getCurrentQueue']);
    Route::get('/patients/{qr_token}/profile', [DoctorForPatientController::class, 'getPatientFullProfile']);
    Route::get('/doctor/{patient}/medical-profile', [DoctorForPatientController::class, 'getPatientFullProfileById']);
    Route::put('/appointments/{appointment}/vital-signs', [MedicalRecordController::class, 'storeVitalSigns']);
    Route::get('doctor/profile', [DoctorForPatientController::class, 'getProfile']);
    //visit home
    // جلب الزيارات الحالية للطبيب
    Route::get('/doctor/home-visits', [DoctorHomeVisitController::class, 'getMyHomeVisits']);
    // بدء التوجه للمريض
    Route::put('/doctor/home-visits/{HomeVisite}/start', [DoctorHomeVisitController::class, 'startVisit']);
    // سجل الزيارات المنتهية
    Route::get('/doctor/home-visits/history', [DoctorHomeVisitController::class, 'getVisitHistory']);
    //Route::get('/doctor-assigned-visits', [DoctorHomeVisitController::class, 'getDoctorAssignedVisits']);
    //سجل طبي في امنزل المريض
    Route::post('/storeMedicalRecord/home-visit', [MedicalRecordController::class, 'storeHomeVisitMedicalRecord']);

    //راشيتة دواء في منزل المريض
    Route::post('/home-visits/{homevisit_id}/prescription', [PrescriptionController::class, 'storePrescriptionHomeVisite']);

    Route::get('/home-visits/{homevisit_id}/prescription', [PrescriptionController::class, 'showPrescriptionHomeVisit']);

    Route::put('/home-visits/{homevisit_id}/prescription', [PrescriptionController::class, 'updatePrescriptionHomeVisit']);

    Route::delete('/home-visits/{homevisit_id}/prescription', [PrescriptionController::class, 'destroyPrescriptionHomeVisit']);
    //تخزين الطلب المختبري في منزل المريض
    Route::post('visits/{visit}/lab-orders', [HomeCareLabOrderController::class, 'store']);
    // عرض تفاصيل طلب مختبر للتعديل
    Route::get('lab-orders/{id}', [HomeCareLabOrderController::class, 'edit']);
    // تحديث طلب مختبر
    Route::put('lab-orders/{id}', [HomeCareLabOrderController::class, 'update']);
    // إلغاء طلب مختبر
    Route::delete('lab-orders/{id}', [HomeCareLabOrderController::class, 'cancel']);

    ////
    // جلب الزيارات الحالية والأرشيف
    Route::get('/doctor/home-visits', [DoctorHomeVisitController::class, 'getMyHomeVisits']);
    Route::get('/doctor/home-visits/history', [DoctorHomeVisitController::class, 'getVisitHistory']);

    // قبول ورفض طلب الزيارة
    Route::put('/doctor/home-visits/{id}/accept', [DoctorHomeVisitController::class, 'acceptVisit']);
    Route::put('/doctor/home-visits/{id}/reject', [DoctorHomeVisitController::class, 'rejectVisit']);

    // تتبع حالة التنقل للزيارة
    Route::put('/doctor/home-visits/{id}/start', [DoctorHomeVisitController::class, 'startVisit']);
    Route::put('/doctor/home-visits/{id}/arrive', [DoctorHomeVisitController::class, 'arriveVisit']);
    Route::put('/doctor/home-visits/{id}/complete', [DoctorHomeVisitController::class, 'completeVisit']);


});



Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/device-token', [NotificationController::class, 'saveToken']);
    Route::delete('/device-token/delete', [NotificationController::class, 'deleteToken']);
    Route::put('/notifications/mark-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::put('/notifications/mark-as-read/{id}', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    Route::get('/notifications/{id}', [NotificationController::class, 'showNotification']);
});

