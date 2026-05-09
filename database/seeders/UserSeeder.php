<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
        'phone'=>'0969551428'
        ],
        [
        'name'=>'ammar',
        'last_name'=>'Ali',
        'email'=>'ammar@gmail.com',
        'phone'=>'0981498112'
        ],
        [
        'name'=>'mohamad',
        'last_name'=>'Ali',
        'email'=>'mohamad@gmail.com',
        'phone'=>'0999459200'
        ],
        [
        'name'=>'jad',
        'last_name'=>'mohammad',
        'email'=>'jad@gmail.com',
        'phone'=>'0969225748'
        ],
        [
        'name'=>'yassin',
        'last_name'=>'ommar',
        'email'=>'yassin@gmail.com',
        'phone'=>'0969552428'
        ],
        [
        'name'=>'saad',
        'last_name'=>'mahmud',
        'email'=>'saad@gmail.com',
        'phone'=>'0969551328'
        ],
        [
        'name'=>'nour',
        'last_name'=>'mahmud',
        'email'=>'nour@gmail.com',
        'phone'=>'0966551328'
        ],
        ];
        foreach($users as $user)
            {
                User::create($user);
            }
    }
}
