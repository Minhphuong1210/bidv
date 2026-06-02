<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSetting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        
    ];
}