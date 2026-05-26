<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SchedulDoctoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
public function run(): void
    {
        //اول 3 اطباء
        $doctors = Doctor::take(3)->get();

        if ($doctors->isEmpty()) {
            $this->command->warn('تنبيه: لم يتم العثور على أطباء في قاعدة البيانات، يرجى ملء جدول الأطباء أولاً.');
            return;
        }

        // الأيام التي سنوزع فيها الدوام
        $days = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء'];

        foreach ($doctors as $doctor) {
            foreach ($days as $day) {
                DoctorSchedule::create([
                    'doctor_id'            => $doctor->id,
                    'day'                  => $day,
                    'start_time'           => '09:00:00',
                    'end_time'             => '17:00:00',
                    'appointment_duration' => 30,
                    'is_active'            => true,
                ]);
            }
        }
                DoctorSchedule::create([
                    'doctor_id'            =>5,
                    'day'                  => 'الأربعاء',
                    'start_time'           => '09:00:00',
                    'end_time'             => '17:00:00',
                    'appointment_duration' => 30,
                    'is_active'            => true,
                ]);
    }
}
