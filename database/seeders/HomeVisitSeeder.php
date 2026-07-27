<?php

namespace Database\Seeders;

use App\Models\HomeVisit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class HomeVisitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
public function run(): void
    {
        // الزيارة الأولى: مكتملة (بتاريخ سابق)
        HomeVisit::create([
            'patient_id'       => 1,
            'clinic_id'        => 1,
            'doctor_id'        => 1,
            'visit_date'       => Carbon::now()->subDays(2)->format('Y-m-d H:i:s'),
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'location_lat'     => 33.51380700,
            'location_lng'     => 36.27652800,
            'status'           => 'completed',
            'rejection_reason' => null,
        ]);

        // الزيارة الثانية: قيد الانتظار (بتاريخ قادم ولم يُحدد لها طبيب بعد)
        HomeVisit::create([
            'patient_id'       => 1,
            'clinic_id'        => 1,
            'doctor_id'        => null,
            'visit_date'       => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'start_time'       => '14:30:00',
            'end_time'         => '15:30:00',
            'location_lat'     => 33.51500000,
            'location_lng'     => 36.28000000,
            'status'           => 'pending',
            'rejection_reason' => null,
        ]);
    }
}
