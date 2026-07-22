<?php

namespace App\Repositories\Eloquent;

use App\Models\Trip;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TripRepository extends BaseRepository implements TripRepositoryInterface
{
    public function __construct(Trip $model)
    {
        parent::__construct($model);
    }

    public function activeForCaptain(int $captainId): Collection
    {
        return $this->model->newQuery()
            ->where('captain_id', $captainId)
            ->whereIn('status', [Trip::STATUS_ACCEPTED, Trip::STATUS_ARRIVED, Trip::STATUS_IN_PROGRESS])
            ->get();
    }

    public function historyForCustomer(int $customerId, int $perPage = 20)
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->orderByDesc('requested_at')
            ->paginate($perPage);
    }

    public function historyForCaptain(int $captainId, int $perPage = 20)
    {
        return $this->model->newQuery()
            ->where('captain_id', $captainId)
            ->orderByDesc('requested_at')
            ->paginate($perPage);
    }

    public function currentForCustomer(int $customerId): ?Trip
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->whereIn('status', [
                Trip::STATUS_REQUESTED, Trip::STATUS_SEARCHING, Trip::STATUS_ACCEPTED,
                Trip::STATUS_ARRIVED, Trip::STATUS_IN_PROGRESS,
            ])
            ->latest('requested_at')
            ->first();
    }

    public function dueScheduledTrips(): Collection
    {
        return $this->model->newQuery()
            ->where('trip_mode', Trip::MODE_SCHEDULED)
            ->where('status', Trip::STATUS_REQUESTED)
            ->where('scheduled_at', '<=', now()->addMinutes(15))
            ->get();
    }

    protected function applyFilters($query, array $filters)
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }
        if (! empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }
        if (! empty($filters['from'])) {
            $query->where('requested_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->where('requested_at', '<=', $filters['to']);
        }

        return $query;
    }
}
