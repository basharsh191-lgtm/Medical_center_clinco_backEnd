<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $specializations = [
        //     ['specialization_name' => 'Cardiology', 'specialization_code' => 'CAR'],//قلبية
        //     ['specialization_name' => 'Dentistry', 'specialization_code' => 'DEN'],//أسنان
        //     ['specialization_name' => 'Dermatology', 'specialization_code' => 'DER'],//جلدية
        //     ['specialization_name' => 'Clinical Nutrition', 'specialization_code' => 'NUT'],//تغذية علاجية
        //     ['specialization_name' => 'ENT', 'specialization_code' => 'ENT'],//أنف وأذن وحنجرة
        //     ['specialization_name' => 'Gastroenterology', 'specialization_code' => 'GIT'],//هضمية
        //     ['specialization_name' => 'Obstetrics & Gynecology', 'specialization_code' => 'OBG'],//نسائية
        //     ['specialization_name' => 'Ophthalmology', 'specialization_code' => 'OPH'],//عينية
        //     ['specialization_name' => 'Pediatrics', 'specialization_code' => 'PED'],//'طب أطفال'
        //     ['specialization_name' => 'Internal Medicine', 'specialization_code' => 'IM'],//باطنية
        //     ['specialization_name' => 'Emergency Medicine', 'specialization_code' => 'ER'],//طوارئ
        // ];
        $specializations = [
            ['specialization_name' => 'أمراض القلب', 'specialization_code' => 'CAR'],
            ['specialization_name' => 'طب الأسنان', 'specialization_code' => 'DEN'],
            ['specialization_name' => 'الأمراض الجلدية', 'specialization_code' => 'DER'],
            ['specialization_name' => 'التغذية العلاجية', 'specialization_code' => 'NUT'],
            ['specialization_name' => 'الأنف والأذن والحنجرة', 'specialization_code' => 'ENT'],
            ['specialization_name' => 'أمراض الجهاز الهضمي', 'specialization_code' => 'GIT'],
            ['specialization_name' => 'النساء والتوليد', 'specialization_code' => 'OBG'],
            ['specialization_name' => 'طب العيون', 'specialization_code' => 'OPH'],
            ['specialization_name' => 'طب الأطفال', 'specialization_code' => 'PED'],
            ['specialization_name' => 'الطب الباطني', 'specialization_code' => 'IM'],
            ['specialization_name' => 'طب الطوارئ', 'specialization_code' => 'ER'],
        ];
        foreach ($specializations as $specialization)
        {
                Specialization::firstOrCreate(
                ['specialization_name' => $specialization['specialization_name']],
                ['specialization_code' => $specialization['specialization_code']]
            );
        }
    }
}
