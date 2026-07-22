<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_fr', 'name_en', 'country_code',
        'center_lat', 'center_lng', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function captains(): HasMany
    {
        return $this->hasMany(Captain::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    /** الاسم حسب لغة الطلب الحالية (ar|fr|en) */
    public function getLocalizedNameAttribute(): string
    {
        $lang = app()->getLocale();
        return $this->{"name_{$lang}"} ?? $this->name_ar;
    }
}
