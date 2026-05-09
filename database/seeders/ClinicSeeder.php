<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
 public function run(): void
    {
        $clinics = [
    [
        'specialization_id' => 1,
        'clinic_name' => 'عيادة القلب',
        'description' => 'تشخيص وعلاج أمراض القلب والشرايين',
        'appointment_duration' => 20,
        'max_appointments_per_day' => 20,
        'open_time' => '08:00:00',
        'close_time' => '16:00:00',
    ],
    [
        'specialization_id' => 2,
        'clinic_name' => 'عيادة الأسنان',
        'description' => 'علاج الأسنان واللثة والتجميل',
        'appointment_duration' => 30,
        'max_appointments_per_day' => 15,
        'open_time' => '09:00:00',
        'close_time' => '18:00:00',
    ],
    [
        'specialization_id' => 3,
        'clinic_name' => 'عيادة الجلدية',
        'description' => 'علاج الأمراض الجلدية والتجميل',
        'appointment_duration' => 15,
        'max_appointments_per_day' => 30,
        'open_time' => '10:00:00',
        'close_time' => '19:00:00',
    ],
    [
        'specialization_id' => 4,
        'clinic_name' => 'عيادة التغذية العلاجية',
        'description' => 'استشارات تغذية وإنقاص الوزن وعلاج السمنة',
        'appointment_duration' => 25,
        'max_appointments_per_day' => 12,
        'open_time' => '09:00:00',
        'close_time' => '17:00:00',
    ],
    [
        'specialization_id' => 5,
        'clinic_name' => 'عيادة الأنف والأذن والحنجرة',
        'description' => 'تشخيص وعلاج أمراض الأذن والأنف والحنجرة',
        'appointment_duration' => 15,
        'max_appointments_per_day' => 25,
        'open_time' => '08:30:00',
        'close_time' => '16:30:00',
    ],
    [
        'specialization_id' => 6,
        'clinic_name' => 'عيادة الجهاز الهضمي',
        'description' => 'تشخيص وعلاج أمراض المعدة والأمعاء والكبد',
        'appointment_duration' => 20,
        'max_appointments_per_day' => 18,
        'open_time' => '09:00:00',
        'close_time' => '17:00:00',
    ],
    [
        'specialization_id' => 7,
        'clinic_name' => 'عيادة النساء والتوليد',
        'description' => 'رعاية الحمل والولادة وأمراض النساء',
        'appointment_duration' => 20,
        'max_appointments_per_day' => 20,
        'open_time' => '08:00:00',
        'close_time' => '18:00:00',
    ],
    [
        'specialization_id' => 8,
        'clinic_name' => 'عيادة العيون',
        'description' => 'فحص وعلاج أمراض العيون وتصحيح النظر',
        'appointment_duration' => 15,
        'max_appointments_per_day' => 30,
        'open_time' => '09:00:00',
        'close_time' => '20:00:00',
    ],
    [
        'specialization_id' => 9,
        'clinic_name' => 'عيادة الأطفال',
        'description' => 'رعاية صحية متكاملة للأطفال من الولادة حتى المراهقة',
        'appointment_duration' => 15,
        'max_appointments_per_day' => 25,
        'open_time' => '09:00:00',
        'close_time' => '17:00:00',
    ],
    [
        'specialization_id' => 10,
        'clinic_name' => 'عيادة الباطنية',
        'description' => 'تشخيص وعلاج أمراض الباطنية والغدد الصماء',
        'appointment_duration' => 20,
        'max_appointments_per_day' => 22,
        'open_time' => '08:00:00',
        'close_time' => '16:00:00',
    ],
    [
        'specialization_id' => 11,
        'clinic_name' => 'عيادة الطوارئ',
        'description' => 'خدمات طبية طارئة على مدار الساعة',
        'appointment_duration' => 10,
        'max_appointments_per_day' => 50,
        'open_time' => '00:00:00',
        'close_time' => '23:59:00',
    ],
        ];

        foreach ($clinics as $clinic)
        {
            Clinic::create($clinic);
        }
    }
}
