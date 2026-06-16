<?php

namespace Database\Seeders;

use App\Models\LabOrder;
use App\Models\LabOrderTest;
use Illuminate\Database\Seeder;

class LabOrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            [
                'appointment_id' => 1,
                'doctor_notes' => 'يرجى إجراء التحاليل التالية',
                'overall_status' => 'pending',
                'tests' => [
                    'CBC',
                    'Vitamin D',
                    'CRP',
                ]
            ],

            [
                'appointment_id' => 2,
                'doctor_notes' => 'مراقبة مستوى السكر',
                'overall_status' => 'completed',
                'tests' => [
                    'HbA1c',
                    'Fasting Blood Sugar',
                ]
            ],

            [
                'appointment_id' => 3,
                'doctor_notes' => 'فحص وظائف الكبد',
                'overall_status' => 'cancelled',
                'tests' => [
                    'ALT',
                    'AST',
                ]
            ],
        ];

        foreach ($orders as $orderData) {

            $tests = $orderData['tests'];
            unset($orderData['tests']);

            $labOrder = LabOrder::create($orderData);

            foreach ($tests as $test) {
                LabOrderTest::create([
                    'lab_order_id' => $labOrder->id,
                    'test_name' => $test,
                    'result_notes' => null,
                ]);
            }
        }
    }
}
