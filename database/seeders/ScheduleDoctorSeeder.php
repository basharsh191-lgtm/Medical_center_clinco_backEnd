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

        // حلقة تكرار تقليدية وواضحة جداً لكل طبيب ولكل يوم
        foreach ($doctors as $doctor) {
            foreach ($days as $day) {
                DoctorSchedule::create([
                    'doctor_id'            => $doctor->id,
                    'day'                  => $day,
                    'start_time'           => '09:00:00', // الدوام يبدأ 9 صباحاً
                    'end_time'             => '17:00:00', // وينتهي 5 عصراً
                    'appointment_duration' => 30,         // مدة الجلسة 30 دقيقة
                    'is_active'            => true,
                ]);
            }
        }
    }
}
