<?php

namespace App\Services;

use App\Models\DeviceTokens;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Factory;
use App\Models\Notification as ModelsNotification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseNotificationService
{
    protected $messaging;

public function __construct()
    {
        // 1. جلب المسار المكتوب في الـ .env عبر الـ config
        $credentials = config('services.firebase.credentials');

        // 2. دمج المسار مع جذر المشروع باستخدام base_path لضمان صيغة المسار المطلق
        $path = ($credentials && file_exists(base_path($credentials)))
            ? base_path($credentials)
            : storage_path('app/firebase/fcm.json'); // مسار احتياطي في حال الـ null

        // 3. بناء الفاكتوري بالمسار الصحيح
        $factory = (new Factory())->withServiceAccount($path);

        // 4. 🚨 السطر الأهم لتعريف الخدمة وتجنب الـ TypeError 🚨
        $this->messaging = $factory->createMessaging();
    }
    public function sendToUser(int $userID, string $title, string $body, array $data = [])
    {
        try
    {
            $tokens=DeviceTokens::where('user_id',$userID)->pluck('token')->toArray();
            if(empty($tokens))
            {
                Log::warning("No device tokens found for user ID: $userID");
                return ['success' => false, 'message' => 'No device tokens found'];
            }
            $notification=ModelsNotification::create([
                'user_id'=>$userID,
                'title'=>$title,
                'body'=>$body,
                'data'=>$data,
                'is_read'=>false
            ]);
            Log::info("Notification saved to database with ID: ".$notification->id);

            // $firebaseNotification=FacadesNotification::create($title,$body);
            $firebaseNotification = \Kreait\Firebase\Messaging\Notification::create($title, $body);
            $message=CloudMessage::new()
            ->withNotification($firebaseNotification)
            ->withData($data);
            $successCount=0;
            $failedCount=0;

            foreach($tokens as $token)
            {
                try{
                    $messageToSend=$message->withChangedTarget('token',$token);
                    $this->messaging->send($messageToSend);
                    $successCount++;
                    Log::info("Notification sent successfully to token: ".substr($token,0,20)."...");
                }catch(\Exception $e)
                {
                    $failedCount++;
                    Log::error("Failed to send notification to token: ".substr($token,0,20)."... Error: ".$e->getMessage());
                }
            }
            Log::info("Notification delivery result - Success: $successCount, Failed: $failedCount");
            return [
                'success' => true,
                'database_id' => $notification->id,
                'sent' => $successCount,
                'failed' => $failedCount
            ];
    }catch(\Exception $e)
    {
        Log::error('Failed to process notification: '.$e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];

    }
    }
}
