<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $guarded = [];
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }
    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }
    public function homeVisit()
    {
        return $this->belongsTo(HomeVisit::class);
    }

}
