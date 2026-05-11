<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users=[
        [
        'name'=>'bashar',
        'last_name'=>'Ali',
        'email'=>'bashar@gmail.com',
        'phone'=>'0969551428',
        'password' => Hash::make('12345678'),
        ],
        [
        'name'=>'ammar',
        'last_name'=>'Ali',
        'email'=>'ammar@gmail.com',
        'phone'=>'0981498112',
        'password' => Hash::make('12345678'),
        ],
        [
        'name'=>'mohamad',
        'last_name'=>'Ali',
        'email'=>'mohamad@gmail.com',
        'phone'=>'0999459200',
        'password' => Hash::make('12345678'),
        ],
        [
        'name'=>'jad',
        'last_name'=>'mohammad',
        'email'=>'jad@gmail.com',
        'phone'=>'0969225748',
        'password' => Hash::make('12345678'),
        ],
        [
        'name'=>'yassin',
        'last_name'=>'ommar',
        'email'=>'yassin@gmail.com',
        'phone'=>'0969552428',
        'password' => Hash::make('12345678'),
        ],
        [
        'name'=>'saad',
        'last_name'=>'mahmud',
        'email'=>'saad@gmail.com',
        'phone'=>'0969551328',
        'password' => Hash::make('12345678'),
        ],
        [
        'name'=>'nour',
        'last_name'=>'mahmud',
        'email'=>'nour@gmail.com',
        'phone'=>'0966551328',
        'password' => Hash::make('12345678'),
        ],
        [
        'name'=>'yara',
        'last_name'=>'bader',
        'email'=>'yara@gmail.com',
        'phone'=>'0986551328',
        'password' => Hash::make('12345678'),
        ],
        ];
        foreach($users as $user)
            {
                User::create($user);
            }
    }
}
