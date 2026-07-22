<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findByPhone(string $phone): ?Customer
    {
        return $this->model->where('phone', $phone)->first();
    }

    protected function applyFilters($query, array $filters)
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('phone', 'like', "%{$filters['search']}%")
                  ->orWhere('full_name', 'like', "%{$filters['search']}%");
            });
        }

        return $query;
    }
}
