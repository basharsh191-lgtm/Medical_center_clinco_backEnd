<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class patient extends Model
{
    protected $guarded = [];

    //هذا الجزء مخصص للأتمتة عند إنشاء سجل جديد
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($patient) {
            $patient->qr_token = (string) Str::uuid();
        });
    }
    protected $appends = ['image_url'];
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }
    public function favoriteDoctors()
{
    return $this->belongsToMany(Doctor::class, 'favorites');
}
}
