<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderTest extends Model
{
protected $guarded = [];
public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }
}
