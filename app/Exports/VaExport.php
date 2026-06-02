<?php

/**
 * Tạo số lượng lớn va và gửi qua bên tele thì chạy vào đây, .....
 *
 *
 */
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
class VaExport implements FromArray, WithHeadings,ShouldAutoSize
{
    protected $data;
    protected $bank;

    public function __construct($data, $bank = '')
    {
        $this->data = $data;
        $this->bank = $bank;
    }

    public function array(): array
    {
        $prefix = config('app.va_name_prefix');

        return array_map(function ($item) use ($prefix) {
            return [
                'Tên chủ tài khoản' => $prefix . ' ' . ($item['merchant_name'] ?? ''),
                'Số tài khoản' => $item['va_number'] ?? '',
                'Ngân hàng' => $this->bank ?: ($item['bank'] ?? '')
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return ['Tên chủ tài khoản', 'Số tài khoản', 'Ngân hàng'];
    }
}