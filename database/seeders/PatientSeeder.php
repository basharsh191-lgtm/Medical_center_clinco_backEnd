<?php

namespace Database\Seeders;

use App\Models\patient;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
$patientsData = [
            [
                'user_id'          => 1,
                'birth_date'       => '1995-05-15',
                'blood_type'       => 'O+',
                'allergies'        => 'No allergies',
                'hereditary'       => 'None',
                'chronic_diseases' => 'None',
                'gender'           => 'male',
                'address'          => 'Damascus, Syria',
                'taller'           => 175,
                'weight'           => 90,
            ],
            [
                'user_id'          => 2,
                'birth_date'       => '2005-05-15',
                'blood_type'       => 'AB+',
                'allergies'        => 'No allergies',
                'hereditary'       => 'None',
                'chronic_diseases' => 'None',
                'gender'           => 'male',
                'address'          => 'Homs, Syria',
                'taller'           => 165,
                'weight'           => 80,
            ],
            // المريض الثالث (لطبيب الأطفال)
            [
                'user_id'          => 9,
                'birth_date'       => '2018-03-10', // طفل
                'blood_type'       => 'A+',
                'allergies'        => 'Penicillin',
                'hereditary'       => 'None',
                'chronic_diseases' => 'Asthma',
                'gender'           => 'male',
                'address'          => 'Aleppo, Syria',
                'taller'           => 120,
                'weight'           => 25,
            ],
            // المريض الرابع (لحالة القلب والجلدية)
            [
                'user_id'          => 10,
                'birth_date'       => '1970-11-22', // رجل كبير
                'blood_type'       => 'B-',
                'allergies'        => 'None',
                'hereditary'       => 'Diabetes',
                'chronic_diseases' => 'Hypertension',
                'gender'           => 'male',
                'address'          => 'Latakia, Syria',
                'taller'           => 180,
                'weight'           => 95,
            ],
        ];

        foreach ($patientsData as $patientData) {
            $user = User::find($patientData['user_id']);
            if ($user) {
                Patient::create($patientData);
                $user->assignRole('patient');
            }
        }
    }
}
