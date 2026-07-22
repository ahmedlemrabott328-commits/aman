<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function find(int|string $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->model->newQuery(), $filters)->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int|string $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }

    /** يمكن للمستودعات الفرعية إعادة تعريفها لدعم فلاتر خاصة بها */
    protected function applyFilters($query, array $filters)
    {
        return $query;
    }
}
