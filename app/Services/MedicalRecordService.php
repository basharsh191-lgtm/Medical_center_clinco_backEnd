<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Exception;

class MedicalRecordService
{
    public function createRecord(array $data): MedicalRecord
    {
        return DB::transaction(function () use ($data) {
            $record = MedicalRecord::create($data);
            $appointment = Appointment::find($data['appointment_id']);
            if ($appointment) {
                $appointment->update(['status' => 'completed']);
            }
            return $record;
        });
    }
}
