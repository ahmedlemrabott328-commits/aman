<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'captain_id', 'vehicle_type_id', 'plate_number', 'brand', 'model', 'year', 'color', 'status',
    ];

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Captain::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
