<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SendOtpRequest;
use App\Http\Requests\Customer\VerifyOtpRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\OtpService;

class AuthController extends Controller
{
    public function __construct(private OtpService $otpService)
    {
    }

    /** إرسال رمز تحقق OTP لرقم الزبون */
    public function sendOtp(SendOtpRequest $request)
    {
        $this->otpService->send($request->phone, 'customer');

        return $this->success(null, 'تم إرسال رمز التحقق');
    }

    /** التحقق من الرمز وتسجيل الدخول (أو إنشاء حساب جديد تلقائيًا) */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        try {
            $this->otpService->verify($request->phone, 'customer', $request->code);
        } catch (OtpException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $customer = Customer::firstOrCreate(
            ['phone' => $request->phone],
            ['full_name' => $request->full_name, 'uuid' => (string) \Illuminate\Support\Str::uuid()],
        );

        if ($customer->isBlocked()) {
            return $this->error('account_blocked', 403);
        }

        $customer->update(['last_login_at' => now()]);
        $token = $customer->createToken('customer-app')->plainTextToken;

        return $this->success([
            'token' => $token,
            'customer' => new CustomerResource($customer),
        ], 'تم تسجيل الدخول بنجاح');
    }

    public function logout()
    {
        auth('sanctum')->user()?->currentAccessToken()?->delete();

        return $this->success(null, 'تم تسجيل الخروج');
    }
}
