<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripAirportDetail extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'trip_id';
    public $incrementing = false;

    protected $fillable = ['trip_id', 'flight_number', 'terminal', 'is_pickup_from_airport'];

    protected $casts = ['is_pickup_from_airport' => 'boolean'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
