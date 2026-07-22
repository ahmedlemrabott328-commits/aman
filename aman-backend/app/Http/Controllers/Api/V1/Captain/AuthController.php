<?php

namespace App\Http\Controllers\Api\V1\Captain;

use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SendOtpRequest; // نفس صيغة رقم الهاتف؛ يمكن فصلها لاحقًا إن لزم
use App\Models\Captain;
use App\Services\OtpService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private OtpService $otpService)
    {
    }

    public function sendOtp(SendOtpRequest $request)
    {
        $this->otpService->send($request->phone, 'captain');

        return $this->success(null, 'تم إرسال رمز التحقق');
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'string', 'size:4'],
            'full_name' => ['required_if:is_new,true', 'nullable', 'string', 'max:150'],
        ]);

        try {
            $this->otpService->verify($data['phone'], 'captain', $data['code']);
        } catch (OtpException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $captain = Captain::firstOrCreate(
            ['phone' => $data['phone']],
            [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'full_name' => $data['full_name'] ?? 'كابتن جديد',
                'approval_status' => Captain::STATUS_PENDING,
            ],
        );

        $captain->update(['last_login_at' => now()]);
        $token = $captain->createToken('captain-app')->plainTextToken;

        return $this->success([
            'token' => $token,
            'captain' => [
                'id' => $captain->id,
                'full_name' => $captain->full_name,
                'phone' => $captain->phone,
                'approval_status' => $captain->approval_status,
            ],
        ], 'تم تسجيل الدخول بنجاح');
    }

    public function logout()
    {
        auth('sanctum')->user()?->currentAccessToken()?->delete();

        return $this->success(null, 'تم تسجيل الخروج');
    }
}
