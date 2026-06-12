<?php

namespace Database\Seeders;

use App\Models\Appointment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
$appointments = [
            [
                'patient_id' => 1,
                'doctor_id' => 1,
                'clinic_id' => 1, // قلب
                'appointment_date' => '2026-05-10',
                'start_time' => '09:00:00',
                'end_time' => '09:25:00',
                'status' => 'completed'
            ],
            [
                'patient_id' => 1,
                'doctor_id' => 4,
                'clinic_id' => 3, // جلدية
                'appointment_date' => now()->toDateString(),
                'start_time' => '11:00:00',
                'end_time' => '11:30:00',
                'status' => 'scheduled'
            ],
            [
                'patient_id' => 1,
                'doctor_id' => 5,
                'clinic_id' => 2, // أسنان
                'appointment_date' => '2026-07-05',
                'start_time' => '13:00:00',
                'end_time' => '13:45:00',
                'status' => 'scheduled'
            ],
            [
                'patient_id' => 2,
                'doctor_id' => 2,
                'clinic_id' => 7, // نسائية
                'appointment_date' => '2026-06-01',
                'start_time' => '10:00:00',
                'end_time' => '10:30:00',
                'status' => 'no_show'
            ],
            [
                'patient_id' => 2,
                'doctor_id' => 5,
                'clinic_id' => 2, // أسنان
                'appointment_date' => '2026-06-25',
                'start_time' => '09:00:00',
                'end_time' => '09:30:00',
                'status' => 'cancelled'
            ],
            //
            [
                'patient_id' => 3,
                'doctor_id' => 3,
                'clinic_id' => 9, // أطفال
                'appointment_date' => '2026-04-15',
                'start_time' => '12:00:00',
                'end_time' => '12:30:00',
                'status' => 'completed'
            ],
            [
                'patient_id' => 3,
                'doctor_id' => 3,
                'clinic_id' => 9, // أطفال
                'appointment_date' => now()->addDays(2)->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '10:30:00',
                'status' => 'scheduled'
            ],
            [
                'patient_id' => 4,
                'doctor_id' => 6,
                'clinic_id' => 1, // قلب (الدكتور الثاني للقلب)
                'appointment_date' => '2026-05-05',
                'start_time' => '16:00:00',
                'end_time' => '16:45:00',
                'status' => 'completed'
            ],
            [
                'patient_id' => 4,
                'doctor_id' => 4,
                'clinic_id' => 3, // جلدية
                'appointment_date' => now()->subDay()->toDateString(),
                'start_time' => '14:00:00',
                'end_time' => '14:30:00',
                'status' => 'scheduled'
            ],
        ];
        foreach ($appointments as $appointment) {
            Appointment::create($appointment);
        }
    }
}
