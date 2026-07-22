<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Captain extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'uuid', 'phone', 'full_name', 'email', 'national_id', 'city_id', 'avatar_url',
        'preferred_lang', 'approval_status', 'approved_by', 'approved_at', 'rejection_reason',
        'is_online', 'current_lat', 'current_lng', 'location_updated_at',
        'rating_avg', 'rating_count', 'fcm_token', 'last_login_at',
    ];

    protected $hidden = ['fcm_token'];

    protected $casts = [
        'is_online' => 'boolean',
        'approved_at' => 'datetime',
        'location_updated_at' => 'datetime',
        'last_login_at' => 'datetime',
        'rating_avg' => 'decimal:2',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'captain_services')->withPivot('is_active');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaptainDocument::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }

    public function isAvailable(): bool
    {
        return $this->isApproved() && $this->is_online;
    }
}
