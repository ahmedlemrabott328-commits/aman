<?php

namespace App\Repositories\Contracts;

use App\Models\Wallet;

interface WalletRepositoryInterface extends BaseRepositoryInterface
{
    public function forCaptain(int $captainId): Wallet;
}
