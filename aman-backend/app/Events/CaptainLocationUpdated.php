<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // بدون قائمة انتظار: يحتاج بث فوري بلا تأخير
use Illuminate\Foundation\Events\Dispatchable;

/** يُبث بمعدل عالٍ نسبيًا (كل بضع ثوانٍ) أثناء الرحلة النشطة لتحريك أيقونة الكابتن على خريطة الزبون */
class CaptainLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $tripId,
        public float $lat,
        public float $lng,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("trip.{$this->tripId}")];
    }

    public function broadcastAs(): string
    {
        return 'captain.location.updated';
    }

    public function broadcastWith(): array
    {
        return ['lat' => $this->lat, 'lng' => $this->lng];
    }
}
