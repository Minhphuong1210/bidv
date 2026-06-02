<?php

namespace App\Services;

use App\Models\User;
use App\Models\VaAccount;

class UserManagerService
{
    public function addUserBalance(int $userId, float $amount, int $type = 1): bool
    {
        $user = User::find($userId);
        if ($user) {
            $user->diem += $amount;
            return $user->save();
        }
        return false;
    }

    public function addVaBalance(string $vaNumber, float $amount, int $type = 1): bool
    {
        $va = VaAccount::where('va_number', $vaNumber)->first();
        if ($va) {
            $va->amount += $amount;
            $va->amount_int += (int) $amount;
            $va->bill_count += 1;
            return $va->save();
        }
        return false;
    }
}
