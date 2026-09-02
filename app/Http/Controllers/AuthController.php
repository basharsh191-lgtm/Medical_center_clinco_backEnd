<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RequestRegister;
use App\Models\DeviceTokens;
use App\Models\User;
use App\Services\OTPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected $OTPService;

    public function __construct(OTPService $OTPService)
    {
        $this->OTPService = $OTPService;
    }
    public function register(RequestRegister $request)
    {
        $validatedData = $request->validated();
        $user = User::firstOrCreate($validatedData);
        $sent = $this->OTPService->handleOtp($user);

        if ($sent) {
            return response()->json([
                'message' => "تم إرسال رمز التحقق إلى بريدك الإلكتروني.",
                'is_verified' => false
            ], 200);
        }

        return response()->json(['message' => 'فشل إرسال الإيميل', 'data' => $user->delete()], 500);
    }
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required',
            'otp'   => 'required'
        ]);
        return $this->OTPService->verifyOtp($data);
    }
    public function resendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|exists:users,phone'
        ]);

        return $this->OTPService->resendOtp($request->phone);
    }
    public function login(LoginRequest $request)
    {
        $request->validated();
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }
        $user = User::where('email', $request->email)->firstOrFail();
        if ($user->is_verified == 0) {
            Auth::logout();

            return response()->json([
                'message' => 'Your account is not verified yet.',
                'is_verified' => false
            ], 403);
        }
        $token = $user->createToken('auth_Token')->plainTextToken;
        return response()->json(
            [
                'massage' => 'User log in Succssfully ',
                'Token' => $token,
                'user_id' => $user->id,
            ],
            201
        );
    }
    public function doctorLogin(Request $request)
    {
        // 1. التحقق من صحة البيانات
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        $user = Auth::user();

        // 2. التاكد من أن المستخدم يحمل دور طبيب عبر Spatie
        if (!$user->hasRole('doctor')) {
            return response()->json([
                'message' => 'هذا الحساب غير مصرح له بالدخول إلى تطبيق الأطباء.'
            ], 403);
        }

        // 3. إنشاء توكن مخصص بدَور الطبيب (Abilities)
        $token = $user->createToken('doctor-app-token', ['access-doctor-app'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }
    public function logout(Request $request)
    {
        $user = $request->user();

        // حذف التوكن الحالي
        $user->currentAccessToken()->delete();

        // حذف جميع Device Tokens الخاصة بالمستخدم
        DeviceTokens::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Logged out successfully',
            'status' => 'success'
        ], 200);
    }
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        return $this->OTPService->sendResetPasswordOtp($request->email);
    }
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'phone'    => 'required',
            'otp'      => 'required',
            'password' => 'required|min:8|confirmed'
        ]);

        return $this->OTPService->resetPasswordWithOtp($data);
    }
}
