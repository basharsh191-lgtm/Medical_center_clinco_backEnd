<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasApiTokens,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'is_verified',
        'last_name',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function patient()
    {
        return $this->hasOne(Patient::class);
    }
    public function doctorProfile()
    {
        return $this->hasOne(Doctor::class);
    }
    public function reception()
    {
        return $this->hasOne(Reception::class);
    }
    public function DeviceTokens()
    {
        return $this->hasMany(DeviceTokens::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    public function customNotifications()
    {
        return $this->hasMany(Notification::class);
    }
    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
