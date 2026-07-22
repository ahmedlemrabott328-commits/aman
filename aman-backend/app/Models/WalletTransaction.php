<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    const UPDATED_AT = null;

    public const TYPE_TRIP_EARNING = 'trip_earning';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_BONUS = 'bonus';
    public const TYPE_PENALTY = 'penalty';

    protected $fillable = [
        'wallet_id', 'trip_id', 'type', 'amount', 'balance_after', 'description', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
