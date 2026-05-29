<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ReceptionistScheduleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClincController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

//Auth
Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);
Route::post('logout',[AuthController::class,'logout'])->middleware('auth:sanctum');
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);
Route::post('/verify_otp',[AuthController::class,'verifyOtp']);
Route::post('/resendOtp',[AuthController::class,'resendOtp']);

//patient
Route::middleware(['auth:sanctum','role:patient'])->group(function () {
    Route::post('/storePatient', [PatientController::class, 'storePatient']);
    Route::get('/showPatient',[PatientController::class,'showPatient']);
    Route::post('/appointmentStore',[PatientController::class,'appointmentStore']);
    Route::put('/appointmentUpdate/{appointment}',[PatientController::class,'appointmentUpdate']);
});

//show home page
//استدعاء العيادة مع دكاترتها
Route::get('/showClinic/{id}',[ClincController::class,'showClinic']);
//عرض بروفايل دكتور معين
Route::get('/showDoctor/{id}',[ClincController::class,'showDoctor']);
//عرض اوقات المتاحة للطبيب
Route::get('doctors/{doctor}/available-slots', [AppointmentController::class, 'getAvailableSlots']);


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

});

