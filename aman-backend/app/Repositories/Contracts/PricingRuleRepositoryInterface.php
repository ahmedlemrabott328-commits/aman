<?php

namespace App\Repositories\Contracts;

use App\Models\PricingRule;

interface PricingRuleRepositoryInterface extends BaseRepositoryInterface
{
    public function activeRuleFor(int $serviceId, int $cityId, ?int $vehicleTypeId = null): ?PricingRule;
}
