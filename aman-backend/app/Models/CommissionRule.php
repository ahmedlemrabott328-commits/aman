<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    protected $fillable = [
        'service_id', 'city_id', 'commission_type', 'value', 'is_active', 'effective_from',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
        'value' => 'decimal:2',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** احتساب مبلغ العمولة على سعر رحلة معيّن */
    public function calculateFor(float $tripPrice): float
    {
        return $this->commission_type === 'percentage'
            ? round($tripPrice * ((float) $this->value / 100), 2)
            : (float) $this->value;
    }
}
