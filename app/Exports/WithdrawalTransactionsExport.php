<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class WithdrawalTransactionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions instanceof Collection ? $transactions : collect($transactions);
    }

    public function headings(): array
    {
        return [
            'TX ID',
            'Số Tài Khoản VA',
            'Tên Merchant',
            'Số Tiền (VND)',
            'Thực Nhận (VND)',
            'Thời Gian Hoàn Tất',
            'Nội Dung',
        ];
    }

    public function map($tx): array
    {
        $merchantName = $tx->vaAccount ? $tx->vaAccount->display_name : 'N/A';

        return [
            $tx->tx_id . ' ',
            $tx->va_number . ' ',
            $merchantName,
            number_format($tx->amount) . ' ',
            number_format($tx->actual_amount) . ' ',
            $tx->completion_time,
            $tx->description,
        ];
    }

    public function collection()
    {
        return $this->transactions;
    }
}
