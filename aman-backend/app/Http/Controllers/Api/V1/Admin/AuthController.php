<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $admin = Admin::where('email', $request->email)->first();

        if (! $admin || ! Hash::check($request->password, $admin->password_hash)) {
            return $this->error('invalid_credentials', 401);
        }

        if (! $admin->is_active) {
            return $this->error('account_disabled', 403);
        }

        $admin->update(['last_login_at' => now()]);
        $token = $admin->createToken('admin-panel')->plainTextToken;

        return $this->success([
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'full_name' => $admin->full_name,
                'email' => $admin->email,
                'roles' => $admin->roles()->pluck('name'),
                'permissions' => $admin->roles()->with('permissions')->get()
                    ->pluck('permissions')->flatten()->pluck('name')->unique()->values(),
            ],
        ], 'تم تسجيل الدخول بنجاح');
    }

    public function logout()
    {
        auth('sanctum')->user()?->currentAccessToken()?->delete();

        return $this->success(null, 'تم تسجيل الخروج');
    }
}
