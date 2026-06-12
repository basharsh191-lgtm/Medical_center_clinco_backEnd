<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
protected $guarded = [];
// العلاقة مع الموعد
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // العلاقة مع تفاصيل التحاليل (One-to-Many)
    public function tests()
    {
        return $this->hasMany(LabOrderTest::class);
    }
}
