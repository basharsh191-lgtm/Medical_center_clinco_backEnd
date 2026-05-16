<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClincController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use App\Mail\Otpmail;
use Illuminate\Http\Request;
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
});

//show home page
//استدعاء العيادة مع دكاترتها
Route::get('/showClinic/{id}',[ClincController::class,'showClinic']);
//عرض بروفايل دكتور معين
Route::get('/showDoctor/{id}',[ClincController::class,'showDoctor']);

//admain
Route::middleware(['auth:sanctum','role:super_admin'])->group(function () {
    //اضافة دكتور
    Route::post('/add/doctor/admin', [StaffController::class, 'storeDoctor']);
    //اضافة ريسبشن
    Route::post('/add/resiption/admin', [StaffController::class, 'storeReception']);
    //اضافة ممرض 
});


