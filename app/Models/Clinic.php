<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $guarded = [];
    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }
    public function reception()
    {
        return $this->hasOne(Reception::class);
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
