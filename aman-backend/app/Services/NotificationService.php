<?php

namespace App\Services;

use App\Models\Captain;
use App\Models\Customer;
use App\Models\Notification;

/**
 * نقطة مركزية واحدة لإرسال أي إشعار: تُسجّله في جدول notifications (ليظهر داخل التطبيق)
 * وتُرسله فوريًا عبر FCM Push. هذا يمنع تكرار نفس المنطق في كل مكان بالكود (TripService,
 * CaptainController...) ويجعل إضافة قناة إشعار جديدة (SMS مثلاً) تعديلاً في مكان واحد فقط.
 */
class NotificationService
{
    public function __construct(private FcmService $fcm)
    {
    }

    public function notifyCustomer(Customer $customer, string $title, string $body, string $type, array $data = []): Notification
    {
        return $this->send('customer', $customer->id, $customer->fcm_token, $title, $body, $type, $data);
    }

    public function notifyCaptain(Captain $captain, string $title, string $body, string $type, array $data = []): Notification
    {
        return $this->send('captain', $captain->id, $captain->fcm_token, $title, $body, $type, $data);
    }

    private function send(string $notifiableType, int $notifiableId, ?string $fcmToken, string $title, string $body, string $type, array $data): Notification
    {
        $notification = Notification::create([
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiableId,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);

        $this->fcm->sendToToken($fcmToken, $title, $body, $data + ['notification_id' => $notification->id]);

        return $notification;
    }
}
