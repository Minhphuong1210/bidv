<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',

        'telegram_id',
        'telegram_username',
        'telegram_first_name',
        'telegram_last_name',
        'telegram_language',
        'telegram_is_bot',
        'telegram_photo',
        'telegram_last_login',
        'telegram_ip',

        'login_count',
        'is_blocked',
        'role',
        'diem',
        'is_approved',
        'chat_id',
        
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}