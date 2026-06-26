<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeVisits extends Model
{
    protected $guarded = [];
    public function doctor()
{
    return $this->belongsTo(Doctor::class, 'doctor_id');
}

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
    }
}
