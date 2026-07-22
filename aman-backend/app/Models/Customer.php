<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable_;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // يمكن استبدالها بـ tymon/jwt-auth حسب اختيار الفريق

class Customer extends Authenticatable_
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    protected $fillable = [
        'uuid', 'phone', 'full_name', 'email', 'gender', 'preferred_lang',
        'avatar_url', 'status', 'fcm_token', 'rating_avg', 'rating_count', 'last_login_at',
    ];

    protected $hidden = ['fcm_token'];

    protected $casts = [
        'last_login_at' => 'datetime',
        'rating_avg' => 'decimal:2',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }
}
