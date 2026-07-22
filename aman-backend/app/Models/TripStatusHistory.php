<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripStatusHistory extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['trip_id', 'status', 'changed_by_type', 'changed_by_id', 'note'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
