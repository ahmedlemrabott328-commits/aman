<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Captain;
use App\Models\Customer;
use App\Models\Trip;
use App\Repositories\Contracts\CaptainRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private CaptainRepositoryInterface $captainRepository)
    {
    }

    public function index(Request $request)
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        $tripsInRange = Trip::whereBetween('requested_at', [$from, $to]);

        return $this->success([
            'customers_total' => Customer::count(),
            'captains_total' => Captain::count(),
            'captains_by_status' => $this->captainRepository->countByApprovalStatus(),
            'captains_online_now' => $this->captainRepository->countOnline(),

            'trips_total' => (clone $tripsInRange)->count(),
            'trips_completed' => (clone $tripsInRange)->where('status', Trip::STATUS_COMPLETED)->count(),
            'trips_cancelled' => (clone $tripsInRange)->where('status', Trip::STATUS_CANCELLED)->count(),

            'revenue' => [
                'gross' => (clone $tripsInRange)->where('status', Trip::STATUS_COMPLETED)->sum('final_price'),
                'commission' => (clone $tripsInRange)->where('status', Trip::STATUS_COMPLETED)->sum('commission_amount'),
            ],

            'trips_by_service' => (clone $tripsInRange)
                ->join('services', 'services.id', '=', 'trips.service_id')
                ->select('services.code')
                ->selectRaw('count(*) as total')
                ->groupBy('services.code')
                ->pluck('total', 'code'),

            'trips_by_day' => (clone $tripsInRange)
                ->selectRaw('DATE(requested_at) as day, count(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day'),
        ]);
    }
}
