<?php

namespace Database\Seeders;

use App\Models\MedicalRecord;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicalRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    $records = [
            [
                'appointment_id'  => 1,
                'patient_id'      => 1,
                'doctor_id'       => 1,
                'chief_complaint' => 'ألم متقطع في الصدر وضيق في التنفس عند بذل مجهود.',
                'diagnosis'       => 'ارتفاع في ضغط الدم مع اشتباه في إجهاد عضلة القلب.',
                'notes'           => 'تم طلب تخطيط قلب (ECG) وتحليل دم شامل. يُنصح بالراحة التامة وتجنب الإجهاد.',
            ],
            [
                'appointment_id'  => 4,
                'patient_id'      => 2,
                'doctor_id'       => 2,
                'chief_complaint' => 'متابعة حمل في الشهر الخامس.',
                'diagnosis'       => 'الحمل مستقر، النبض الطبيعي للجنين.',
                'notes'           => 'الاستمرار على الفيتامينات الموصوفة وحمض الفوليك.',
            ],
            [
                'appointment_id'  => 6,
                'patient_id'      => 3,
                'doctor_id'       => 3,
                'chief_complaint' => 'مراجعة دورية لمتابعة مستويات النمو.',
                'diagnosis'       => 'النمو طبيعي ومستقر.',
                'notes'           => 'استجابة ممتازة، تم التأكيد على الالتزام بجدول التطعيمات.',
            ],
            [
                'appointment_id'  => 9,
                'patient_id'      => 4,
                'doctor_id'       => 4,
                'chief_complaint' => 'طفح جلدي وحكة مستمرة منذ أسبوع.',
                'diagnosis'       => 'إكزيما تلامسية.',
                'notes'           => null,
            ],
        ];
        foreach ($records as $record) {
            MedicalRecord::create($record);
        }
    }
}
