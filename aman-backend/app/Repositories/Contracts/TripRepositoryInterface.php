<?php

namespace App\Repositories\Contracts;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

interface TripRepositoryInterface extends BaseRepositoryInterface
{
    /** جلب الرحلات النشطة لكابتن معيّن */
    public function activeForCaptain(int $captainId): Collection;

    /** جلب سجل رحلات زبون مع ترقيم صفحات */
    public function historyForCustomer(int $customerId, int $perPage = 20);

    /** جلب سجل رحلات كابتن مع ترقيم صفحات */
    public function historyForCaptain(int $captainId, int $perPage = 20);

    /** جلب الرحلة الحالية (النشطة) للزبون إن وجدت */
    public function currentForCustomer(int $customerId): ?Trip;

    /** الرحلات المجدولة القادمة خلال نافذة زمنية (لمهمة الـ Queue التي تفعّل البحث عن كابتن) */
    public function dueScheduledTrips(): Collection;
}
