<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripDeliveryDetail extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'trip_id';
    public $incrementing = false;

    protected $fillable = [
        'trip_id', 'receiver_name', 'receiver_phone',
        'package_description', 'package_size', 'delivery_notes',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
