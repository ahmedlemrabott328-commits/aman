<?php

namespace App\Repositories\Eloquent;

use App\Models\Captain;
use App\Repositories\Contracts\CaptainRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CaptainRepository extends BaseRepository implements CaptainRepositoryInterface
{
    public function __construct(Captain $model)
    {
        parent::__construct($model);
    }

    public function nearbyAvailable(float $lat, float $lng, float $radiusKm, int $serviceId, array $excludeIds = []): Collection
    {
        // يعتمد على عمود PostGIS geography(Point) المُحدَّث عبر location_updated_at
        $point = "ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography";

        $query = $this->model->newQuery()
            ->select('captains.*')
            ->selectRaw("ST_Distance(current_location, {$point}) / 1000 AS distance_km")
            ->join('captain_services', 'captain_services.captain_id', '=', 'captains.id')
            ->where('captain_services.service_id', $serviceId)
            ->where('captain_services.is_active', true)
            ->where('captains.is_online', true)
            ->where('captains.approval_status', Captain::STATUS_APPROVED)
            ->whereRaw("ST_DWithin(current_location, {$point}, ?)", [$radiusKm * 1000])
            ->orderBy('distance_km');

        if (! empty($excludeIds)) {
            $query->whereNotIn('captains.id', $excludeIds);
        }

        return $query->get();
    }

    public function pendingApproval(int $perPage = 20)
    {
        return $this->model->newQuery()
            ->where('approval_status', Captain::STATUS_PENDING)
            ->with('documents')
            ->orderBy('created_at')
            ->paginate($perPage);
    }

    public function countByApprovalStatus(): array
    {
        return $this->model->newQuery()
            ->selectRaw('approval_status, count(*) as total')
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status')
            ->toArray();
    }

    public function countOnline(): int
    {
        return $this->model->newQuery()->where('is_online', true)->count();
    }

    protected function applyFilters($query, array $filters)
    {
        if (! empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }
        if (! empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }
        if (array_key_exists('is_online', $filters)) {
            $query->where('is_online', $filters['is_online']);
        }
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('phone', 'like', "%{$filters['search']}%")
                  ->orWhere('full_name', 'like', "%{$filters['search']}%");
            });
        }

        return $query->with('city');
    }
}
