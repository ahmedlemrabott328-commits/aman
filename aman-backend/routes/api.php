<?php

use App\Http\Controllers\Api\V1\Captain\AuthController as CaptainAuthController;
use App\Http\Controllers\Api\V1\Captain\StatusController as CaptainStatusController;
use App\Http\Controllers\Api\V1\Captain\TripController as CaptainTripController;
use App\Http\Controllers\Api\V1\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\V1\Customer\TripController as CustomerTripController;
use App\Http\Controllers\DocumentPreviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AMAN API Routes (v1)
|--------------------------------------------------------------------------
| كل مسار محمي بـ auth:sanctum ما عدا OTP (تسجيل الدخول).
| بوابة الإدارة (Admin) منفصلة تمامًا في admin.php لتطبيق RBAC عليها وحدها.
*/

// بديل تطويري محلي لعرض وثائق الكباتن عندما لا يكون قرص s3 مُفعَّلاً (راجع
// DocumentStorageService::temporaryUrl). محمي بـ auth:sanctum + تحقق ملكية داخل الـ Controller.
Route::middleware('auth:sanctum')
    ->get('documents/preview/{path}', [DocumentPreviewController::class, 'show'])
    ->name('documents.preview');

Route::prefix('v1')->group(function () {

    // ===================== الزبون =====================
    Route::prefix('customer')->group(function () {
        Route::post('auth/send-otp', [CustomerAuthController::class, 'sendOtp']);
        Route::post('auth/verify-otp', [CustomerAuthController::class, 'verifyOtp']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('auth/logout', [CustomerAuthController::class, 'logout']);

            Route::post('trips/estimate', [CustomerTripController::class, 'estimate']);
            Route::post('trips', [CustomerTripController::class, 'store']);
            Route::get('trips/current', [CustomerTripController::class, 'current']);
            Route::get('trips/history', [CustomerTripController::class, 'history']);
            Route::get('trips/{id}', [CustomerTripController::class, 'show']);
            Route::post('trips/{id}/cancel', [CustomerTripController::class, 'cancel']);
            Route::post('trips/{id}/rate', [CustomerTripController::class, 'rate']);
        });
    });

    // ===================== الكابتن =====================
    Route::prefix('captain')->group(function () {
        Route::post('auth/send-otp', [CaptainAuthController::class, 'sendOtp']);
        Route::post('auth/verify-otp', [CaptainAuthController::class, 'verifyOtp']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('auth/logout', [CaptainAuthController::class, 'logout']);
            Route::get('documents', [\App\Http\Controllers\Api\V1\Captain\DocumentController::class, 'index']);
            Route::post('documents', [\App\Http\Controllers\Api\V1\Captain\DocumentController::class, 'store']);

            Route::post('status/toggle', [CaptainStatusController::class, 'toggle']);
            Route::post('status/location', [CaptainStatusController::class, 'updateLocation']);

            Route::post('trips/{id}/accept', [CaptainTripController::class, 'accept']);
            Route::post('trips/{id}/reject', [CaptainTripController::class, 'reject']);
            Route::post('trips/{id}/arrived', [CaptainTripController::class, 'arrived']);
            Route::post('trips/{id}/start', [CaptainTripController::class, 'start']);
            Route::post('trips/{id}/complete', [CaptainTripController::class, 'complete']);
            Route::get('trips/history', [CaptainTripController::class, 'history']);

            Route::get('wallet', [\App\Http\Controllers\Api\V1\Captain\WalletController::class, 'show']);
            Route::get('earnings', [\App\Http\Controllers\Api\V1\Captain\WalletController::class, 'earnings']);
        });
    });
});
