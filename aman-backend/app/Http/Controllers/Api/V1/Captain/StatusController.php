<?php

namespace App\Http\Controllers\Api\V1\Captain;

use App\Events\CaptainLocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /** تغيير حالة الاتصال (متصل/غير متصل) */
    public function toggle(Request $request)
    {
        $data = $request->validate(['is_online' => ['required', 'boolean']]);

        $captain = $request->user();

        if ($data['is_online'] && ! $captain->isApproved()) {
            return $this->error('account_not_approved', 403);
        }

        $captain->update(['is_online' => $data['is_online']]);

        return $this->success(['is_online' => $captain->is_online]);
    }

    /** تحديث الموقع الحالي (يُستدعى دوريًا من التطبيق) */
    public function updateLocation(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $captain = $request->user();
        $captain->update([
            'current_lat' => $data['lat'],
            'current_lng' => $data['lng'],
            'location_updated_at' => now(),
        ]);

        // تحديث عمود PostGIS geography عبر استعلام خام لدقة الفهرسة المكانية
        \DB::statement(
            'UPDATE captains SET current_location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$data['lng'], $data['lat'], $captain->id]
        );

        // بث الموقع لحظيًا للزبون فقط إذا كانت هناك رحلة نشطة قيد التنفيذ
        $activeTrip = Trip::where('captain_id', $captain->id)
            ->whereIn('status', [Trip::STATUS_ACCEPTED, Trip::STATUS_ARRIVED, Trip::STATUS_IN_PROGRESS])
            ->first();

        if ($activeTrip) {
            broadcast(new CaptainLocationUpdated($activeTrip->id, $data['lat'], $data['lng']));
        }

        return $this->success(null);
    }
}
