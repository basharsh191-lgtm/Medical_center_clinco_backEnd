<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function speciality()
    {
        return $this->belongsTo(Specialization::class);
    }
    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    // طريقة لجلب الجداول النشطة فقط
    public function activeSchedules()
    {
        return $this->hasMany(DoctorSchedule::class)->where('is_active', true);
    }
}
