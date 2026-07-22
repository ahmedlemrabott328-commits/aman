<?php

use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\CaptainController;
use App\Http\Controllers\Api\V1\Admin\CityController;
use App\Http\Controllers\Api\V1\Admin\CommissionRuleController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\PricingController;
use App\Http\Controllers\Api\V1\Admin\TripController;
use App\Http\Controllers\Api\V1\Admin\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AMAN Admin API Routes
|--------------------------------------------------------------------------
| كل مسار (عدا login) محمي بـ auth:sanctum + middleware صلاحية (permission:xxx)
| مطبَّق عبر RBAC (roles/permissions). راجع app/Http/Middleware/CheckPermission.php
*/

Route::prefix('v1/admin')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::middleware('permission:customers.view')->group(function () {
            Route::get('customers', [CustomerController::class, 'index']);
            Route::get('customers/{id}', [CustomerController::class, 'show']);
            Route::post('customers/{id}/block', [CustomerController::class, 'block'])->middleware('permission:customers.manage');
        });

        Route::middleware('permission:captains.view')->group(function () {
            Route::get('captains', [CaptainController::class, 'index']);
            Route::get('captains/pending', [CaptainController::class, 'pending']);
            Route::get('captains/{id}', [CaptainController::class, 'show']);
            Route::post('captains/{id}/approve', [CaptainController::class, 'approve'])->middleware('permission:captains.approve');
            Route::post('captains/{id}/reject', [CaptainController::class, 'reject'])->middleware('permission:captains.approve');
            Route::post('captains/{id}/suspend', [CaptainController::class, 'suspend'])->middleware('permission:captains.manage');
        });

        Route::middleware('permission:captains.approve')->group(function () {
            Route::post('captains/{captainId}/documents/{documentId}/review', [CaptainController::class, 'reviewDocument']);
        });

        Route::middleware('permission:trips.view')->group(function () {
            Route::get('trips', [TripController::class, 'index']);
            Route::get('trips/{id}', [TripController::class, 'show']);
        });

        Route::middleware('permission:pricing.manage')->group(function () {
            Route::apiResource('pricing-rules', PricingController::class);
            Route::apiResource('commission-rules', CommissionRuleController::class);
        });

        Route::middleware('permission:cities.manage')->group(function () {
            Route::apiResource('cities', CityController::class);
        });

        Route::middleware('permission:wallets.view')->group(function () {
            Route::get('wallets', [WalletController::class, 'index']);
            Route::get('wallets/{captainId}', [WalletController::class, 'show']);
            Route::post('wallets/{captainId}/adjust', [WalletController::class, 'adjust'])->middleware('permission:wallets.manage');
        });
    });
});
