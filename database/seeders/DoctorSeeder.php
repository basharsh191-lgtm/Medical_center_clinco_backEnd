<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors=[
            [
                'user_id' => 3,
                'specialization_id' => 1,
                'clinic_id' => 1,
                'image' => 'http://127.0.0.1:8000/storage/doctor_picture/3.jpg',
                'experience_years' => rand(2, 20),
                'number_operations'=>20,
                'bio' => "خبير في مجاله الطبي ولديه خبرة واسعة في التعامل مع الحالات الحرجةالقلبية.",
            ],
            [
                'user_id' => 4,
                'specialization_id' => 7,
                'clinic_id' => 7,
                'image' => 'http://127.0.0.1:8000/storage/doctor_picture/4.jpg',
                'experience_years' => rand(2, 20),
                'bio' => "خبير في مجال التوليد و النسائية.",
                'number_operations'=>20,
            ],
                        [
                'user_id' => 5,
                'specialization_id' => 9,
                'clinic_id' => 9,
                'image' => 'http://127.0.0.1:8000/storage/doctor_picture/5.jpg',
                'experience_years' => rand(2, 20),
                'bio' => "طبيب متخصص بصحة الطفل منذ الولادة حتى المراهقة. يتابع التطعيمات والنمو والتطور البدني والعقلي",
            ],
            [
                'user_id' => 6,
                'specialization_id' => 3,
                'clinic_id' => 3,
                'image' => 'http://127.0.0.1:8000/storage/doctor_picture/6.jpg',
                'experience_years' => rand(2, 20),
                'bio' => "خبير في التعامل مع الحساسية والجلدية",
            ],
            [
                'user_id' => 7,
                'specialization_id' => 2,
                'clinic_id' => 2,
                'image' => 'http://127.0.0.1:8000/storage/doctor_picture/7.jpg',
                'experience_years' => rand(2, 20),
                'bio' => 'تشخيص وعلاج التسوس، خلع الأسنان التالفة، تركيب الحشوات والجسور والتيجان، وتجميل الابتسامة وتبييض الأسنان.',
            ],
            [
                'user_id' => 8,
                'specialization_id' => 1,
                'clinic_id' => 1,
                'image' => 'http://127.0.0.1:8000/storage/doctor_picture/8.jpg',
                'experience_years' => rand(2, 20),
                'bio' => "خبير في مجاله الطبي ولديه خبرة واسعة في التعامل مع الحالات الحرجةالقلبية.",
            ],
        ];
        foreach($doctors as $doctor)
            {
            $user = User::find($doctor['user_id']);
            if ($user) {
                Doctor::create($doctor);
                $user->assignRole('doctor');
            }
            }
    }
}
