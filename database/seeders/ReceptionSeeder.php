<?php

namespace Database\Seeders;

use App\Models\Reception;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReceptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $receptionData = [
            [
                'user_id' =>9,
                'clinic_id' => 1,
                'salary' => 1500.00,
                'hiring_date' => '2024-01-01',
                'status' => 'active',
                'shift_type' => 'morning',
                'biography' => 'موظف استقبال ذو خبرة 5 سنوات في مجال الاستقبال الطبي',
            ],
            [
                'user_id' => 10,
                'clinic_id' => 2,
                'salary' => 1400.00,
                'hiring_date' => '2024-03-15',
                'status' => 'active',
                'shift_type' => 'evening',
                'biography' => 'متخصص في التعامل مع المرضى وخدمة العملاء',
            ],
        ];
        foreach($receptionData as $reception)
            {
            $user = User::find($reception['user_id']);
            if ($user) {
                Reception::create($reception);
                $user->assignRole('reception');
            }
            }
    }
}
