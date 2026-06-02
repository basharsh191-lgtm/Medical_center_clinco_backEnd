<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $guarded = [];
    public function rateable()
    {
        return $this->morphTo();
    }
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
