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
                'taller'            => 175,
                'weight'            => 90,
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
                'taller'            => 165,
                'weight'            => 80,
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
