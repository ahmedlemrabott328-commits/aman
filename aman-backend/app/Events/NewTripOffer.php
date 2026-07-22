<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * يُبث لكابتن محدد عند ترشيحه لرحلة، بالتوازي مع Push Notification (احتياطًا إن كان
 * التطبيق مفتوحًا بالفعل فلا حاجة لانتظار FCM). القناة خاصة بالكابتن وليس بالرحلة
 * لأن الكابتن لم يقبل الرحلة بعد.
 */
class NewTripOffer implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $captainId,
        public Trip $trip,
        public int $offerTimeoutSeconds = 15,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("captain.{$this->captainId}")];
    }

    public function broadcastAs(): string
    {
        return 'trip.offer.new';
    }

    public function broadcastWith(): array
    {
        return [
            'trip_id' => $this->trip->id,
            'pickup_address' => $this->trip->pickup_address,
            'pickup_lat' => $this->trip->pickup_lat,
            'pickup_lng' => $this->trip->pickup_lng,
            'estimated_price' => $this->trip->estimated_price,
            'currency' => $this->trip->currency,
            'offer_timeout_seconds' => $this->offerTimeoutSeconds,
        ];
    }
}
