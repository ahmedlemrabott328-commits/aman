<?php

use App\Models\Trip;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| النظام يملك 3 أنواع مستخدمين (customer/captain/admin) بحراسات Sanctum منفصلة،
| لذا التفويض هنا يتحقق يدويًا من كل حارس بدل الاعتماد على auth()->user() الافتراضي.
| Frontend يرسل توكن الحارس المناسب في هيدر Authorization عند طلب /broadcasting/auth.
*/

Broadcast::channel('trip.{tripId}', function ($user, int $tripId) {
    $trip = Trip::find($tripId);

    if (! $trip) {
        return false;
    }

    // Sanctum polymorphic tokenable: $user هو بالفعل نموذج Customer|Captain|Admin
    // الصحيح المطابق لصاحب التوكن المُرسَل، بغض النظر عن نوعه.
    if ($user instanceof \App\Models\Customer) {
        return $user->id === $trip->customer_id;
    }

    if ($user instanceof \App\Models\Captain) {
        return $user->id === $trip->captain_id;
    }

    if ($user instanceof \App\Models\Admin) {
        return true; // الأدمن يمكنه مراقبة أي رحلة (لوحة المراقبة اللحظية)
    }

    return false;
});

Broadcast::channel('captain.{captainId}', function ($user, int $captainId) {
    return $user instanceof \App\Models\Captain && $user->id === $captainId;
});

Broadcast::channel('customer.{customerId}', function ($user, int $customerId) {
    return $user instanceof \App\Models\Customer && $user->id === $customerId;
});
