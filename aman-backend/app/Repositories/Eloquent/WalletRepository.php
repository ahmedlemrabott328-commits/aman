<?php

namespace App\Repositories\Eloquent;

use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;

class WalletRepository extends BaseRepository implements WalletRepositoryInterface
{
    public function __construct(Wallet $model)
    {
        parent::__construct($model);
    }

    public function forCaptain(int $captainId): Wallet
    {
        return $this->model->firstOrCreate(['captain_id' => $captainId], ['balance' => 0, 'currency' => 'MRU']);
    }
}
