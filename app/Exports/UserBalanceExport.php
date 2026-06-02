<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class UserBalanceExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $users;

    public function __construct($users)
    {
        // Accept both Collection and array
        $this->users = $users instanceof Collection ? $users : collect($users);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Telegram ID',
            'Username',
            'Họ Tên',
            'Số Dư (Điểm)',
        ];
    }

    public function collection(): Collection
    {
        return $this->users->map(function ($u) {
            $u = is_array($u) ? (object) $u : $u;
            return [
                $u->id,
                $u->telegram_id . ' ',
                '@' . ($u->telegram_username ?? 'N/A'),
                $u->name ?: ($u->telegram_first_name ?? 'N/A'),
                number_format($u->diem ?? 0) . ' ',
            ];
        });
    }
}
