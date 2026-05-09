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
    public function doctor()
    {
        return $this->hasMany(Doctor::class);
    }
}
