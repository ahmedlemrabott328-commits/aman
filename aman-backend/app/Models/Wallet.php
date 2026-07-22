<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = ['captain_id', 'balance', 'currency'];

    protected $casts = ['balance' => 'decimal:2'];

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Captain::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
