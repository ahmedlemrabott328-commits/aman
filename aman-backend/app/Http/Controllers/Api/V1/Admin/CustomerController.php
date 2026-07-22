<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminCustomerResource;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private AuditLogService $auditLog,
    ) {
    }

    public function index(Request $request)
    {
        $customers = $this->customerRepository->paginate(
            perPage: (int) $request->get('per_page', 20),
            filters: $request->only(['status', 'search']),
        );

        return $this->success(AdminCustomerResource::collection($customers)->response()->getData(true));
    }

    public function show(int $id)
    {
        $customer = $this->customerRepository->findOrFail($id);
        $customer->loadCount('trips');

        return $this->success(new AdminCustomerResource($customer));
    }

    /** حظر/رفع حظر عن زبون */
    public function block(Request $request, int $id)
    {
        $data = $request->validate(['blocked' => ['required', 'boolean']]);

        $customer = $this->customerRepository->findOrFail($id);
        $oldStatus = $customer->status;
        $newStatus = $data['blocked'] ? 'blocked' : 'active';

        $customer->update(['status' => $newStatus]);

        $this->auditLog->log(
            $request->user(), $data['blocked'] ? 'customer.blocked' : 'customer.unblocked',
            $customer, ['status' => $oldStatus], ['status' => $newStatus], $request->ip(),
        );

        return $this->success(new AdminCustomerResource($customer->fresh()), 'تم تحديث حالة الزبون');
    }
}
