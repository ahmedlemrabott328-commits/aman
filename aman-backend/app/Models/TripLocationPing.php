<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripLocationPing extends Model
{
    public $timestamps = false;

    protected $fillable = ['trip_id', 'lat', 'lng', 'recorded_at'];

    protected $casts = ['recorded_at' => 'datetime'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
