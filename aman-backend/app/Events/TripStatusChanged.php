<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * يُبث عند أي تغيير في حالة الرحلة (accepted, arrived, in_progress, completed, cancelled...)
 * يستمع له تطبيقا الزبون والكابتن على نفس القناة الخاصة بالرحلة لتحديث الشاشة فوريًا
 * دون الحاجة لـ Polling متكرر على الـ API.
 */
class TripStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Trip $trip)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("trip.{$this->trip->id}")];
    }

    public function broadcastAs(): string
    {
        return 'trip.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'trip_id' => $this->trip->id,
            'status' => $this->trip->status,
            'captain_id' => $this->trip->captain_id,
            'updated_at' => $this->trip->updated_at,
        ];
    }
}
