<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| الجدولة الدورية (Scheduled Tasks)
|--------------------------------------------------------------------------
| يتطلب تشغيل: * * * * * php artisan schedule:run على cron الخادم (كل دقيقة).
*/

// تنظيف رموز OTP المنتهية يوميًا
Schedule::command('aman:prune-expired-otps')->daily();

// تفعيل الرحلات المجدولة القريبة من موعدها (تحويلها إلى "searching" لبدء البحث عن كابتن)
Schedule::call(function () {
    app(\App\Repositories\Contracts\TripRepositoryInterface::class)
        ->dueScheduledTrips()
        ->each(function (\App\Models\Trip $trip) {
            $trip->update(['status' => \App\Models\Trip::STATUS_SEARCHING]);
            \App\Jobs\DispatchTripJob::dispatch($trip->id);
        });
})->everyFiveMinutes()->name('activate-due-scheduled-trips')->withoutOverlapping();
