<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins=[
        [
        'name'=>'admin1',
        'last_name'=>'Ali',
        'email'=>'basharalshayyah@gmail.com',
        'phone'=>'0966667777',
        'password' => Hash::make('12345678'),
        'is_verified'=>1
        ],
        [
        'name'=>'admin2',
        'last_name'=>'Ali',
        'email'=>'ammashayyahr@gmail.com',
        'phone'=>'0955556666',
        'password' => Hash::make('12345678'),
        'is_verified'=>1
        ],
        ];
        foreach($admins as $admin)
            {
                $user=User::create($admin);
                $user->assignRole('super_admin');

            }
    }
}
