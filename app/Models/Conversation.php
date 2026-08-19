<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // جلب آخر رسالة في المحادثة
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
