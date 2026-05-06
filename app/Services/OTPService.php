<?php
namespace App\Services;

use App\Mail\Otpmail;
use App\Models\User;
use App\Models\otp_user;
use App\Notifications\OtpNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OTPService
{
    //موقف ارسال ال otp
public function sendOtpToEmail($email, $otp, $name = null)
{
    try {
        //لح وقفها للسرعة
       // Mail::to($email)->send(new Otpmail($otp, $name));
        return response()->json([
        'status' => true,
        'message' => 'تم إرسال الرمز إلى بريدك الإلكتروني.',
        'expires_at' => now()->addMinutes(10)
    ]);
    } catch (\Exception $e) {
        Log::error('Failed to send OTP email: ' . $e->getMessage());
        return false;
    }
}
public function handleOtp(User $user)
{
    $otp = rand(100000, 999999);
    otp_user::updateOrCreate(
        ['phone' => $user->phone],
        [
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10)
        ]
    );
    if ($user->email) {
        return $this->sendOtpToEmail($user->email, $otp, $user->name);
    }
    Log::warning('User has no email address', ['user_id' => $user->id]);
    return false;
}
public function verifyOtp(array $data)
{
    $otpRecord = otp_user::where('phone', $data['phone'])
        ->where('otp', $data['otp'])
        ->where('expires_at', '>', now())
        ->first();

    if (!$otpRecord) {
        return response()->json(['message' => 'انتهت صلاحية الرمز الرجاء اعادة ارساله مرة اخرى'], 422);
    }
    $user = User::firstOrCreate(
        ['phone' => $data['phone']],
        ['is_verified' => true]
    );
    $user->update(['is_verified' => true]);
    $user->assignRole('patient');
    $otpRecord->delete();
    $token = $user->createToken('auth_Token')->plainTextToken;
    return response()->json([
        'message' => 'OTP verified successfully.',
        'user' => $user,
        'token'=>$token,
    ]);
}
public function resendOtp(string $phone)
{
    $otpEntry = otp_user::where('phone', $phone)->first();
//lessThan لمقارنة بين زمنين
    if ($otpEntry && now()->lessThan($otpEntry->expires_at)) {
        return response()->json([
            'status' => false,
            'message' => 'الرمز الحالي لا يزال صالحاً. يرجى الانتظار حتى انتهاء صلاحيته.',
            'expires_at' => $otpEntry->expires_at
        ], 400);
    }

    $newOtp = rand(100000, 999999);

    $user = User::where('phone', $phone)->first();
    $otpEntry = otp_user::updateOrCreate(
    ['phone' => $phone],
    [
        'otp' => $newOtp,
        'expires_at' => now()->addMinutes(10),
        'created_at' => now()
    ]
    );
    if ($user && $user->email) {
        return $this->sendOtpToEmail($user->email, $newOtp, $user->name);
    }

    return response()->json([
        'status' => true,
        'message' => 'تم إرسال رمز جديدك.',
        'expires_at' => $otpEntry->expires_at
    ]);
}

}


