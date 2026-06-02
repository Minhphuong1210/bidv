<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'tx_id',
        'va_number',
        'amount',
        'actual_amount',
        'fee_rate',
        'completion_time',
        'user_id',
        'description',
        'is_manual',
        'is_redeemed',
        'withdrawal_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'actual_amount' => 'float',
        'fee_rate' => 'integer',
        'is_manual' => 'integer',
        'is_redeemed' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vaAccount()
    {
        return $this->belongsTo(VaAccount::class , 'va_number', 'va_number');
    }
}
