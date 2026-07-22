<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CaptainRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * أقرب الكباتن المتاحين ضمن نصف قطر معيّن (بالكيلومتر) يقدّمون خدمة معيّنة.
     */
    public function nearbyAvailable(float $lat, float $lng, float $radiusKm, int $serviceId, array $excludeIds = []): Collection;

    public function pendingApproval(int $perPage = 20);

    public function countByApprovalStatus(): array;

    public function countOnline(): int;
}
