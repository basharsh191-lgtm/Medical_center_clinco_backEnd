<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRegister;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RequestRegister;
use App\Services\OTPService;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\delete;

class UserController extends Controller
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

    return response()->json(['message' => 'فشل إرسال الإيميل','data'=>delete($user)], 500);
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
        if(!Auth::attempt($request->only('email','password')))
        {
            return response()->json(['message'=>'Invalid email or password'], 401);
        }
        $user=User::where('email',$request->email)->firstOrFail();
        $token=$user->createToken('auth_Token')->plainTextToken;
        return response()->json(
            ['massage'=>'User log in Succssfully ',
                    'Token'=>$token
                    ], 201);
}
public function logout(Request $request)
{
    $user = $request->user();
    $user->update(['is_verified' => false]);
    $user->currentAccessToken()->delete();
    return response()->json([
    'message' => 'The log out successfully',
    'is_verified' => false
    ], 200);
}
}
