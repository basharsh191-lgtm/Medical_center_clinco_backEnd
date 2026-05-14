<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdmainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins=[
        [
        'name'=>'admain1',
        'last_name'=>'Ali',
        'email'=>'basharalshayyah@gmail.com',
        'phone'=>'0966667777',
        'password' => Hash::make('12345678'),
        'is_verified'=>'true'
        ],
        [
        'name'=>'admain2',
        'last_name'=>'Ali',
        'email'=>'ammashayyahr@gmail.com',
        'phone'=>'0955556666',
        'password' => Hash::make('12345678'),
        'is_verified'=>'true'
        ],
        ];
        foreach($admins as $admin)
            {
                $user=User::create($admin);
                $user->assignRole('super_admin');

            }
    }
}
