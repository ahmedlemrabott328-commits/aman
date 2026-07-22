<?php

namespace App\Services;

use App\Models\PricingRule;
use App\Repositories\Contracts\PricingRuleRepositoryInterface;

class PricingService
{
    public function __construct(
        private PricingRuleRepositoryInterface $pricingRuleRepository,
    ) {
    }

    /**
     * حساب السعر التقديري بناءً على المسافة والوقت وفق قاعدة التسعير الفعّالة.
     * يُستخدم عند "عرض السعر التقديري" وعند إنشاء الرحلة.
     */
    public function estimate(int $serviceId, int $cityId, float $distanceKm, int $durationMin, ?int $vehicleTypeId = null): array
    {
        $rule = $this->pricingRuleRepository->activeRuleFor($serviceId, $cityId, $vehicleTypeId);

        if (! $rule) {
            throw new \RuntimeException('لا توجد قاعدة تسعير فعّالة لهذه الخدمة/المدينة');
        }

        $price = (float) $rule->base_fare
            + ($distanceKm * (float) $rule->price_per_km)
            + ($durationMin * (float) $rule->price_per_minute);

        $price = max($price, (float) $rule->min_fare);

        return [
            'pricing_rule_id' => $rule->id,
            'estimated_price' => round($price, 2),
            'currency' => $rule->currency,
            'breakdown' => [
                'base_fare' => (float) $rule->base_fare,
                'distance_charge' => round($distanceKm * (float) $rule->price_per_km, 2),
                'time_charge' => round($durationMin * (float) $rule->price_per_minute, 2),
                'min_fare_applied' => $price == (float) $rule->min_fare,
            ],
        ];
    }
}
