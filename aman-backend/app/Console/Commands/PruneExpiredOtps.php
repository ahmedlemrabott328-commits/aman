<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Command;

class PruneExpiredOtps extends Command
{
    protected $signature = 'aman:prune-expired-otps';
    protected $description = 'حذف رموز OTP المنتهية الصلاحية (أمن + تنظيف الجدول دوريًا)';

    public function handle(): int
    {
        $count = OtpCode::where('expires_at', '<', now()->subDay())->delete();
        $this->info("تم حذف {$count} رمز OTP منتهي الصلاحية.");

        return self::SUCCESS;
    }
}
