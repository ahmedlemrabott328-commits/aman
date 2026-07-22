<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['phone', 'user_type', 'code', 'is_used', 'attempts', 'expires_at'];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
