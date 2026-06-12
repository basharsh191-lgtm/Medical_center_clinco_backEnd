<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $guarded = [];
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }
    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }
    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }
    public function labOrders()
    {
        return $this->hasMany(LabOrder::class);
    }
}
