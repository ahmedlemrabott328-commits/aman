<?php

namespace App\Repositories\Eloquent;

use App\Models\PricingRule;
use App\Repositories\Contracts\PricingRuleRepositoryInterface;

class PricingRuleRepository extends BaseRepository implements PricingRuleRepositoryInterface
{
    public function __construct(PricingRule $model)
    {
        parent::__construct($model);
    }

    /** أحدث قاعدة تسعير فعّالة لخدمة/مدينة/نوع مركبة معيّن */
    public function activeRuleFor(int $serviceId, int $cityId, ?int $vehicleTypeId = null): ?PricingRule
    {
        return $this->model->newQuery()
            ->where('service_id', $serviceId)
            ->where('city_id', $cityId)
            ->when($vehicleTypeId, fn ($q) => $q->where('vehicle_type_id', $vehicleTypeId))
            ->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->orderByDesc('effective_from')
            ->first();
    }
}
