<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VaAccount extends Model
{
    protected $table = 'va_accounts';

    protected $fillable = [
        'user_id',
        'va_number',
        'merchant_name',
        'bank',
        'bank_full',
        'type',
        'amount',
        'amount_int',
        'bill_count',
        'status',
        'created_date',
        'created_by',
        'fee_rate',
        'custom_excel_file',
        'ma_don_hang',
        'quick_link',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_int' => 'integer',
        'bill_count' => 'integer',
        'fee_rate' => 'integer',
        'created_date' => 'datetime',
    ];

    // display name format
    public function getDisplayNameAttribute(): string
    {
        $prefix = config('hpay.name_prefix', 'QUY');
        $name = $this->merchant_name ?? '';

        $clean = preg_replace('/^(PQ |' . preg_quote($prefix) . ' )/i', '', $name);

        return $prefix . ' ' . $clean;
    }

    // relation user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // transactions VA
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'va_number', 'va_number');
    }
}