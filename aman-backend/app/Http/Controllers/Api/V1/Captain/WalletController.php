<?php

namespace App\Http\Controllers\Api\V1\Captain;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private WalletRepositoryInterface $walletRepository)
    {
    }

    /** عرض المحفظة — القسم 5/10 */
    public function show(Request $request)
    {
        $wallet = $this->walletRepository->forCaptain($request->user()->id);

        return $this->success(new WalletResource($wallet));
    }

    /** عرض الأرباح مع سجل الحركات */
    public function earnings(Request $request)
    {
        $wallet = $this->walletRepository->forCaptain($request->user()->id);
        $transactions = $wallet->transactions()->latest()->paginate(20);

        return $this->success([
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'transactions' => WalletTransactionResource::collection($transactions)->response()->getData(true),
        ]);
    }
}
