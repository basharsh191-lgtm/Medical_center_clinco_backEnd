<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    protected $guarded = [];
    public function clinic()
    {
        return $this->hasOne(Clinic::class);
    }
}
