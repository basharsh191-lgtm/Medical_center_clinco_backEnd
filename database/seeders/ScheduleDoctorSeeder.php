<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScheduleDoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
public function run(): void
    {
        $doctors = Doctor::all();

        if ($doctors->isEmpty()) {
            $this->command->warn('تنبيه: لم يتم العثور على أطباء في قاعدة البيانات، يرجى ملء جدول الأطباء أولاً.');
            return;
        }

        // الأيام التي سنوزع فيها الدوام
        $days = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];

        foreach ($doctors as $doctor) {
            foreach ($days as $day) {

                // 1. تحديد نوع الدوام عشوائياً (كررنا clinic لتكون الاحتمالية الأكبر)
                $scheduleType = fake()->randomElement(['clinic', 'clinic', 'home_visit', 'both']);

                // 2. تحديد مدة الجلسة برمجياً بناءً على نوع الدوام
                $duration = match($scheduleType) {
                    'home' => 60,  // ساعة كاملة للزيارة المنزلية (تشمل الفحص + وقت الطريق)
                    'both' => 45,  // 45 دقيقة كحل وسط إذا كان اليوم يدمج بين الاثنين
                    default => 30, // 30 دقيقة كافتراضي للعيادة فقط
                };

                DoctorSchedule::create([
                    'doctor_id'            => $doctor->id,
                    'day'                  => $day,
                    'start_time'           => '09:00:00',
                    'end_time'             => '17:00:00',
                    'appointment_duration' => $duration,       // استخدام المدة الديناميكية
                    'schedule_type'        => $scheduleType,   // الحقل الجديد الذي أضفناه للـ Migration
                    'is_active'            => true,
                ]);
            }
        }

        $this->command->info('تم زراعة جداول الأطباء (عيادات وزيارات منزلية) بنجاح!');
    }
}
