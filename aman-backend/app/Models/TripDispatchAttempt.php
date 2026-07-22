<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripDispatchAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = ['trip_id', 'captain_id', 'search_radius_km', 'status', 'offered_at', 'responded_at'];

    protected $casts = [
        'offered_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Captain::class);
    }
}
