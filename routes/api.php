<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ReceptionistScheduleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClincController;
use App\Http\Controllers\DoctorHomeVisitController;
use App\Http\Controllers\HomeVisitController;
use App\Http\Controllers\LabOrderController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReceptionHomeVisitController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

//Auth
Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login'])->middleware('throttle:5,1'); // تقييد محاولات تسجيل الدخول لمنع الهجمات
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
    Route::get('/prescription/{appointment}', [PrescriptionController::class, 'getAppointmentPrescription']);
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
});

//reception
Route::middleware(['auth:sanctum','role:reception'])->group(function () {
//اضافة جدول لدكتور معين داخل عيادة مخصصة
Route::post('store/schedule/reception',[ReceptionistScheduleController::class,'storeSchedule']);
//جلب كل الاطباء في العيادة التي يشتغل بها الريسبشن
Route::get('get/doctors/reception',[ReceptionistScheduleController::class,'getMyClinicDoctors']);
//تحويل حالة الموعد من  scheduled الىarrived
Route::post('/receptionist/check-in', [ReceptionistScheduleController::class, 'checkInByPatientQR']);

//home visit
Route::get('/show-home-visits', [ReceptionHomeVisitController::class, 'getClinicHomeVisits']);
Route::post('/approve-home-visit/{id}', [ReceptionHomeVisitController::class, 'approveAndAssignDoctor']);
Route::post('/reject-home-visit/{id}', [ReceptionHomeVisitController::class, 'rejectVisit']);



});
//doctor
Route::middleware(['auth:sanctum','role:doctor'])->group(function () {
    //تخزين السجل الطبي للمريض وإغلاق الموعد
    Route::post('/storeMedicalRecord', [MedicalRecordController::class, 'storeMedicalRecord']);
    Route::post('appointments/{appointment}/prescription', [PrescriptionController::class, 'storePrescription']);
    Route::post('appointments/{appointment}/lab-orders', [LabOrderController::class, 'storeLabOrderDoctor']);
    Route::delete('appointments/lab-orders/{id}', [LabOrderController::class, 'cancelLabOrder']);

    //visit home
    //Route::get('/doctor-assigned-visits', [DoctorHomeVisitController::class, 'getDoctorAssignedVisits']);


});
