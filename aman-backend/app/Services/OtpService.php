<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Notification; // أو بوابة SMS خارجية (Twilio/Vonage/محلي)

class OtpService
{
    private const CODE_LENGTH = 4;
    private const EXPIRES_IN_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;

    /** توليد وإرسال رمز التحقق */
    public function send(string $phone, string $userType): void
    {
        $code = str_pad((string) random_int(0, 9999), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone' => $phone,
            'user_type' => $userType,
            'code' => $code,
            'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
        ]);

        // TODO: ربط ببوابة SMS فعلية (محلية موريتانية أو Twilio)
        // SmsGateway::send($phone, "رمز التحقق AMAN: {$code}");
    }

    /** التحقق من صحة الرمز؛ يرمي استثناء برسالة مناسبة عند الفشل */
    public function verify(string $phone, string $userType, string $code): bool
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('user_type', $userType)
            ->where('is_used', false)
            ->latest('id')
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            throw new \App\Exceptions\OtpException('otp_expired_or_not_found');
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw new \App\Exceptions\OtpException('otp_max_attempts_reached');
        }

        if ($otp->code !== $code) {
            $otp->increment('attempts');
            throw new \App\Exceptions\OtpException('otp_invalid');
        }

        $otp->update(['is_used' => true]);

        return true;
    }
}
