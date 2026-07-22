<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trip extends Model
{
    use HasFactory;

    // ==== حالات الرحلة ====
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_SEARCHING = 'searching';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_CAPTAIN_FOUND = 'no_captain_found';

    // ==== أنماط الرحلة ====
    public const MODE_INSTANT = 'instant';
    public const MODE_SCHEDULED = 'scheduled';
    public const MODE_OPEN = 'open';

    protected $fillable = [
        'uuid', 'trip_code', 'customer_id', 'captain_id', 'vehicle_id',
        'service_id', 'city_id', 'trip_mode', 'status',
        'pickup_address', 'pickup_lat', 'pickup_lng',
        'dropoff_address', 'dropoff_lat', 'dropoff_lng',
        'scheduled_at', 'distance_km', 'duration_min',
        'estimated_price', 'final_price', 'currency',
        'pricing_rule_id', 'commission_rule_id', 'commission_amount',
        'cancelled_by_type', 'cancelled_by_id', 'cancel_reason',
        'requested_at', 'accepted_at', 'arrived_at', 'started_at', 'completed_at', 'cancelled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'arrived_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'estimated_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Trip $trip) {
            $trip->uuid ??= (string) \Illuminate\Support\Str::uuid();
            $trip->trip_code ??= 'TR-' . strtoupper(\Illuminate\Support\Str::random(8));
            $trip->requested_at ??= now();
        });
    }

    // ==== العلاقات ====
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Captain::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class);
    }

    public function deliveryDetails(): HasOne
    {
        return $this->hasOne(TripDeliveryDetail::class);
    }

    public function airportDetails(): HasOne
    {
        return $this->hasOne(TripAirportDetail::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TripStatusHistory::class)->orderBy('created_at');
    }

    public function dispatchAttempts(): HasMany
    {
        return $this->hasMany(TripDispatchAttempt::class);
    }

    public function locationPings(): HasMany
    {
        return $this->hasMany(TripLocationPing::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // ==== قواعد عمل مساعدة ====
    public function isCancellable(): bool
    {
        return in_array($this->status, [
            self::STATUS_REQUESTED, self::STATUS_SEARCHING,
            self::STATUS_ACCEPTED, self::STATUS_ARRIVED,
        ], true);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACCEPTED, self::STATUS_ARRIVED, self::STATUS_IN_PROGRESS,
        ], true);
    }
}
