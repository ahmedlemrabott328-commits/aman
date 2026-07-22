<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptainDocument extends Model
{
    protected $fillable = [
        'captain_id', 'document_type', 'file_path', 'status',
        'reviewed_by', 'reviewed_at', 'rejection_reason', 'expires_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'expires_at' => 'date',
    ];

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Captain::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
}
