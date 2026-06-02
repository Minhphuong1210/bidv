<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBankAccount extends Model
{
    protected $table = 'user_bank_accounts';

    protected $fillable = [
        'user_id',
        'stk',
        'bank',
        'name',
        'qr_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class , 'user_id', 'id');
    }
}