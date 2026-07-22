<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
    ) {
    }

    /**
     * تسجيل حركة على المحفظة (إضافة أو خصم) بشكل ذري (Atomic) مع تحديث الرصيد.
     * $amount: موجب = إضافة | سالب = خصم
     */
    public function recordTransaction(int $captainId, string $type, float $amount, ?int $tripId = null, ?string $description = null, ?int $adminId = null): WalletTransaction
    {
        return DB::transaction(function () use ($captainId, $type, $amount, $tripId, $description, $adminId) {
            /** @var Wallet $wallet */
            $wallet = $this->walletRepository->forCaptain($captainId);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            $newBalance = (float) $wallet->balance + $amount;
            $wallet->update(['balance' => $newBalance]);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'trip_id' => $tripId,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $description,
                'created_by' => $adminId,
            ]);
        });
    }

    /** تسجيل أرباح الرحلة وخصم العمولة المستحقة على الكابتن دفعة واحدة */
    public function settleTrip(int $captainId, int $tripId, float $tripEarning, float $commissionAmount): void
    {
        DB::transaction(function () use ($captainId, $tripId, $tripEarning, $commissionAmount) {
            $this->recordTransaction($captainId, WalletTransaction::TYPE_TRIP_EARNING, $tripEarning, $tripId, 'أرباح الرحلة');
            if ($commissionAmount > 0) {
                $this->recordTransaction($captainId, WalletTransaction::TYPE_COMMISSION, -$commissionAmount, $tripId, 'عمولة المنصة');
            }
        });
    }
}
