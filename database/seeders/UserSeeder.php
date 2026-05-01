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
        $user=User::create([
        'name'=>'bashar',
        'last_name'=>'Ali',
        'email'=>'bashar@gmail.com',
        'phone'=>'0969551428'
        ]);
    }
}
