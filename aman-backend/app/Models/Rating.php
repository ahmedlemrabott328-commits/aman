<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['trip_id', 'customer_id', 'captain_id', 'score', 'comment'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Captain::class);
    }
}
