<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'إرسال تذكير للمرضى بمواعيدهم القادمة';

    private const WINDOW_MINUTES = 5;

    public function handle(): int
    {
        // 1. تذكير قبل 24 ساعة
        $this->sendRemindersFor(24 * 60, 'reminder_24h_sent', 'تذكير بموعدك غداً', function ($appointment) {
            return "نريد تذكيرك أن موعدك القادم هو غداً بتاريخ {$appointment->appointment_date} الساعة {$appointment->start_time}.";
        });

        // 2. تذكير قبل ساعة
        $this->sendRemindersFor(60, 'reminder_1h_sent', 'تذكير بموعدك القريب', function ($appointment) {
            return "نريد تذكيرك أن موعدك القادم هو اليوم الساعة {$appointment->start_time}.";
        });

        return self::SUCCESS;
    }

    private function sendRemindersFor(int $minutesBefore, string $flagColumn, string $title, callable $bodyResolver): void
    {
        $now = Carbon::now();

        // نجيب المواعيد المجدولة اللي لسا ما انبعتلها هاد التذكير
        $candidates = Appointment::where('status', 'scheduled')
            ->where($flagColumn, false)
            ->whereIn('appointment_date', [
                $now->copy()->toDateString(),
                $now->copy()->addDay()->toDateString(),
            ])
            ->get();

        foreach ($candidates as $appointment) {
            $appointmentDateTime = Carbon::parse($appointment->appointment_date . ' ' . $appointment->start_time);
            $diffInMinutes = $now->diffInMinutes($appointmentDateTime, false);

            // فحص هل الموعد يقع ضمن نافذة التذكير؟
            if ($diffInMinutes <= $minutesBefore && $diffInMinutes > ($minutesBefore - self::WINDOW_MINUTES)) {
                $patientUserId = $appointment->patient?->user_id;

                if ($patientUserId) {
                    NotificationService::sendToUser(
                        $patientUserId,
                        $title,
                        $bodyResolver($appointment),
                        ['appointment_id' => $appointment->id, 'type' => 'appointment_reminder']
                    );
                }

                // تحديث الخانة عشان ما يتكرر الإشعار
                $appointment->update([$flagColumn => true]);

                $this->info("تم إرسال التذكير بنجاح للموعد #{$appointment->id}");
            }
        }
    }
}
