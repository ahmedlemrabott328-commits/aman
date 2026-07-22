<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CityRequest;
use App\Http\Resources\CityResource;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function __construct(
        private CityRepositoryInterface $cityRepository,
        private AuditLogService $auditLog,
    ) {
    }

    public function index(Request $request)
    {
        $cities = $this->cityRepository->paginate(
            perPage: (int) $request->get('per_page', 50),
            filters: $request->only(['is_active', 'search']),
        );

        return $this->success(CityResource::collection($cities)->response()->getData(true));
    }

    public function store(CityRequest $request)
    {
        $city = $this->cityRepository->create($request->validated());
        $this->auditLog->log($request->user(), 'city.created', $city, [], $city->toArray(), $request->ip());

        return $this->success(new CityResource($city), 'تم إضافة المدينة', 201);
    }

    public function show(int $id)
    {
        return $this->success(new CityResource($this->cityRepository->findOrFail($id)));
    }

    public function update(CityRequest $request, int $id)
    {
        $city = $this->cityRepository->findOrFail($id);
        $old = $city->toArray();

        $city = $this->cityRepository->update($id, $request->validated());

        $this->auditLog->log($request->user(), 'city.updated', $city, $old, $city->toArray(), $request->ip());

        return $this->success(new CityResource($city), 'تم تحديث المدينة');
    }

    public function destroy(Request $request, int $id)
    {
        $city = $this->cityRepository->findOrFail($id);
        $this->auditLog->log($request->user(), 'city.deleted', $city, $city->toArray(), [], $request->ip());
        $this->cityRepository->delete($id);

        return $this->success(null, 'تم حذف المدينة');
    }
}
