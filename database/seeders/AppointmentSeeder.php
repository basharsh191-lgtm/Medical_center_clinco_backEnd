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
        $appoientment=
        [
        'patient_id'=>'1',
        'doctor_id'=>'1',
        'clinic_id'=>'1',
        'appointment_date'=>'2026-05-26',
        'start_time'=>'09:00:00',
        'end_time'=>'09:25:00',
        ];
        Appointment::create($appoientment);
    }
}
