<?php

namespace App\Repositories\Eloquent;

use App\Models\City;
use App\Repositories\Contracts\CityRepositoryInterface;

class CityRepository extends BaseRepository implements CityRepositoryInterface
{
    public function __construct(City $model)
    {
        parent::__construct($model);
    }

    protected function applyFilters($query, array $filters)
    {
        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        }
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name_ar', 'like', "%{$filters['search']}%")
                  ->orWhere('name_fr', 'like', "%{$filters['search']}%")
                  ->orWhere('name_en', 'like', "%{$filters['search']}%");
            });
        }

        return $query;
    }
}
