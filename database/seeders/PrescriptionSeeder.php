<?php

namespace Database\Seeders;

use App\Models\Prescription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrescriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
$prescription1 = Prescription::create([
            'appointment_id' => 1,
            'instructions'   => 'الالتزام بأخذ الدواء في موعده، وتقليل الملح في الطعام والابتعاد عن التوتر.',
        ]);

        $prescription1->items()->createMany([
            [
                'medicine_name' => 'Aspirin (أسبرين)',
                'dosage'        => '81mg',
                'frequency'     => 'مرة واحدة يومياً بعد الغداء',
                'duration'      => 'مستمر',
            ],
            [
                'medicine_name' => 'Concor (كونكور)',
                'dosage'        => '5mg',
                'frequency'     => 'قرص واحد صباحاً',
                'duration'      => 'لمدة شهر',
            ]
        ]);

        $prescription2 = Prescription::create([
            'appointment_id' => 4,
            'instructions'   => 'الإكثار من شرب السوائل الدافئة والراحة التامة في السرير لمدة يومين.',
        ]);

        $prescription2->items()->createMany([
            [
                'medicine_name' => 'Panadol Advance (بنادول)',
                'dosage'        => '500mg',
                'frequency'     => 'عند اللزوم (بحد أقصى 4 مرات يومياً)',
                'duration'      => 'لمدة 3 أيام',
            ],
            [
                'medicine_name' => 'Azithromycin (أزيثروميسين)',
                'dosage'        => '500mg',
                'frequency'     => 'مرة واحدة يومياً',
                'duration'      => 'لمدة 3 أيام',
            ],
            [
                'medicine_name' => 'Prospan Syrup (شراب بروسبان)',
                'dosage'        => '7.5ml',
                'frequency'     => '3 مرات يومياً',
                'duration'      => 'لمدة أسبوع',
            ]
        ]);

        $prescription3 = Prescription::create([
            'appointment_id' => 7,
            'instructions'   => 'مراقبة مستوى السكر في الدم يومياً قبل الإفطار وتسجيل القراءات للطبيب.',
        ]);

        $prescription3->items()->createMany([
            [
                'medicine_name' => 'Glucophage (جلوكوفاج)',
                'dosage'        => '1000mg',
                'frequency'     => 'مرتين يومياً بعد الأكل',
                'duration'      => 'مستمر حتى المراجعة القادمة',
            ]
        ]);
    }

}
