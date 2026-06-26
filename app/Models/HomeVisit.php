<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeVisit extends Model
{
    protected $guarded = [];
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
