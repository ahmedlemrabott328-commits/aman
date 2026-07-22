<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    protected $fillable = [
        'service_id', 'city_id', 'vehicle_type_id', 'base_fare', 'price_per_km',
        'price_per_minute', 'min_fare', 'cancellation_fee', 'currency',
        'is_active', 'effective_from', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
        'base_fare' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'price_per_minute' => 'decimal:2',
        'min_fare' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }
}
