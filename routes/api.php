<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\ClincController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\UserController;
use App\Mail\Otpmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register',[UserController::class,'register']);
Route::post('/login',[UserController::class,'login']);
Route::post('logout',[UserController::class,'logout'])->middleware('auth:sanctum');

Route::post('/verify_otp',[UserController::class,'verifyOtp']);
Route::post('/resendOtp',[UserController::class,'resendOtp']);

Route::middleware(['auth:sanctum','role:patient'])->group(function () {
    Route::post('/storePatient', [PatientController::class, 'storePatient']);
    Route::get('/showPatient/{id}',[PatientController::class,'showPatient']);
});
//استدعاء العيادة مع دكاترتها
Route::get('/showClinic/{id}',[ClincController::class,'showClinic']);
//عرض بروفايل دكتور معين
Route::get('/showDoctor/{id}',[ClincController::class,'showDoctor']);




