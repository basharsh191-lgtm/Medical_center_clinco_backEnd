<?php

namespace Database\Seeders;

use App\Models\Rating;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctorModel = 'App\Models\Doctor';
    $ratings = [
                [
                    'patient_id'    => 1,
                    'rateable_type' => $doctorModel,
                    'rateable_id'   => 1,
                    'stars'         => 5,
                    'comment'       => 'طبيب ممتاز جداً، استمع لشكوتي بعناية ووصف العلاج المناسب.',
                ],
                [
                    'patient_id'    => 2,
                    'rateable_type' => $doctorModel,
                    'rateable_id'   => 1,
                    'stars'         => 4,
                    'comment'       => 'تشخيص دقيق، ولكن مدة الانتظار في العيادة كانت طويلة نوعاً ما.',
                ],
                [
                    'patient_id'    => 3,
                    'rateable_type' => $doctorModel,
                    'rateable_id'   => 2,
                    'stars'         => 5,
                    'comment'       => 'أفضل طبيب تعاملت معه، خلوق جداً ومحترف.',
                ],
                [
                    'patient_id'    => 4,
                    'rateable_type' => $doctorModel,
                    'rateable_id'   => 2,
                    'stars'         => 3,
                    'comment'       => 'طبيب جيد، لكن الشرح عن الحالة لم يكن وافياً بالنسبة لي.',
                ],
                [
                    'patient_id'    => 1,
                    'rateable_type' => $doctorModel,
                    'rateable_id'   => 3,
                    'stars'         => 2,
                    'comment'       => 'لم أستفد من العلاج الموصوف، والزيارة كانت سريعة جداً.',
                ],
                [
                    'patient_id'    => 2,
                    'rateable_type' => $doctorModel,
                    'rateable_id'   => 3,
                    'stars'         => 5,
                    'comment'       => 'تجربة ممتازة، العيادة نظيفة والطبيب ذو خبرة عالية.',
                ],
                [
                    'patient_id'    => 4,
                    'rateable_type' => $doctorModel,
                    'rateable_id'   => 4,
                    'stars'         => 4,
                    'comment'       => 'طبيب رائع وأنصح به.',
                ],
            ];

            foreach ($ratings as $rating) {
                Rating::create($rating);
            }
    }
}
