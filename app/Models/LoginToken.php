<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginToken extends Model
{
    protected $fillable = [
        'token',
        'telegram_id',
        'user_id',
        'used',
        'expired_at'
    ];
}