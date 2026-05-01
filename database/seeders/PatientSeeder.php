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
        $user = User::first();

        if ($user) {
            patient::create([
                'user_id'          => $user->id,
                'birth_date'       => '1995-05-15',
                'blood_type'       => 'O+',
                'allergies'        => 'No allergies',
                'hereditary'       => 'None',
                'chronic_diseases' => 'None',
                'gender'           => 'male',
                'address'          => 'Damascus, Syria',
            ]);
            $user->assignRole('patient');
    }
}
}
