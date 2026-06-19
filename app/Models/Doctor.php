<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ParentIterator;

class Doctor extends Model
{
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function speciality()
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
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
        return $this->hasMany(DoctorSchedule::class);
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    public function ratings()
    {
        return $this->morphMany(Rating::class, 'rateable');
    }
    // طريقة لحساب متوسط التقييمات
    public function averageRating()
    {
        return $this->ratings()->avg('stars') ?? 0;
    }
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }
    public function favoriteByPatients()
    {
        return $this->belongsToMany(Patient::class, 'favorites');
    }
    }
