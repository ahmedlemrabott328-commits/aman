<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WalletAdjustRequest;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\AuditLogService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService,
        private AuditLogService $auditLog,
    ) {
    }

    public function index(Request $request)
    {
        $wallets = Wallet::query()
            ->with('captain:id,full_name,phone')
            ->when($request->search, fn ($q) => $q->whereHas('captain', fn ($c) => $c
                ->where('phone', 'like', "%{$request->search}%")
                ->orWhere('full_name', 'like', "%{$request->search}%")))
            ->paginate((int) $request->get('per_page', 20));

        return $this->success(WalletResource::collection($wallets)->response()->getData(true));
    }

    public function show(int $captainId)
    {
        $wallet = Wallet::with('captain:id,full_name,phone')->where('captain_id', $captainId)->firstOrFail();
        $transactions = $wallet->transactions()->latest()->paginate(20);

        return $this->success([
            'wallet' => new WalletResource($wallet),
            'transactions' => WalletTransactionResource::collection($transactions)->response()->getData(true),
        ]);
    }

    /** تعديل يدوي على رصيد الكابتن (مكافأة، تصحيح خطأ، ...) — موثَّق بالكامل في Audit Log */
    public function adjust(WalletAdjustRequest $request, int $captainId)
    {
        $transaction = $this->walletService->recordTransaction(
            captainId: $captainId,
            type: WalletTransaction::TYPE_ADJUSTMENT,
            amount: (float) $request->amount,
            description: $request->description,
            adminId: $request->user()->id,
        );

        $this->auditLog->log(
            $request->user(), 'wallet.adjusted', $transaction,
            [], $transaction->toArray(), $request->ip(),
        );

        return $this->success(new WalletTransactionResource($transaction), 'تم تعديل الرصيد');
    }
}
