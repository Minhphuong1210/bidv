<?php

namespace App\Http\Services;

use App\Models\VaAccount;
use Illuminate\Support\Str;
class VaService
{
    public function __construct()
    {
    }

    private function getMid($bank)
    {
        return null;
    }

    public function createVa($name, $bank, $accountLength = 10)
    {
        $name = $this->normalizeName($name);
        $merchantName = env('VA_NAME_PREFIX', 'TRAN') . ' ' . $name;
        
        $vaNumber = $this->generateUniqueVaNumber($accountLength);

        $va = VaAccount::create([
            'user_id' => auth()->id() ?? 1,
            'va_number' => $vaNumber,
            'merchant_name' => $merchantName,
            'bank' => 'BIDV', // Use BIDV internally for custom VAs
            'bank_full' => 'BIDV',
            'type' => 1,
            'amount' => 0,
            'amount_int' => 0,
            'bill_count' => 0,
            'status' => 1,
            'created_date' => now(),
            'created_by' => auth()->id() ?? 1,
            'fee_rate' => env('DEFAULT_FEE_RATE', 8),
            'quick_link' => null,
        ]);


        $va->update([
            'ma_don_hang' => 'DH'
                . now()->format('ymdHis')
                . $va->id
                . rand(100, 999)
        ]);

        return [true, $va];
    }
    
    public function generateUniqueVaNumber($length = 10)
    {
        do {
            $vaNumber = '';
            $vaNumber .= rand(1, 9); // First digit not zero
            for ($i = 1; $i < $length; $i++) {
                $vaNumber .= rand(0, 9);
            }
        } while (VaAccount::where('va_number', $vaNumber)->exists());
        
        return $vaNumber;
    }

    public function history()
    {
        return VaAccount::where('user_id', auth()->id())
            ->orderByDesc('id')->limit(10)
            ->get();
    }

    public function generateNames($prefix, $quantity, $nameLength)
    {
        $namePool = $this->getNamePool();
        $envPrefix = strtolower(env('VA_NAME_PREFIX', 'TRAN'));

        $names = [];
        $displayNames = [];

        for ($i = 0; $i < $quantity; $i++) {
            $randomWords = $this->getRandomWords($namePool, $nameLength);
            $suffix = implode(' ', $randomWords);

            $apiName = $this->normalizeName(trim($prefix . ' ' . $suffix));
            $names[] = $apiName;

            $displayNames[] = $this->normalizeName(trim($envPrefix . ' ' . $apiName));
        }

        return [$names, $displayNames];
    }

    public function getNamePool()
    {
        return [
            'chu',
            'anh',
            'ha',
            'nam',
            'minh',
            'duc',
            'hoang',
            'quang',
            'huy',
            'dat',
            'tuan',
            'phuong',
            'linh',
            'trang',
            'lan',
            'huong',
            'thao',
            'vy',
            'ngoc',
            'khanh',
            'long',
            'bao',
            'son',
            'tien',
            'thai',
            'vinh',
            'cuong',
            'dung',
            'hiep',
            'phuc',
            'an',
            'binh',
            'nhat',
            'kien',
            'duy',
            'kiet',
            'trung',
            'nghia',
            'tai',
            'loc',
            'phat',
            'khoa',
            'tri',
            'vu',
            'hieu',
            'giang',
            'thien',
            'khang',
            'lam',
            'quy',
            'nhan',
            'thanh',
            'hien',
            'phu',
            'doan',
            'dang',
            'toan',
            'hung',
            'viet',
            'thinh',
            'phong',
            'uyen',
            'nga',
            'chi',
            'quyen',
            'mai',
            'huyen',
            'tuyen',
            'dao',
            'kim',
            'ngan',
            'loan',
            'y',
            'bach',
            'tung',
            'quoc',
            'vuong',
            'duong',
            'giap',
            'tu',
            'hanh',
            'thu',
            'thuong',
            'han',
            'nguyen',
            'le',
            'tran',
            'vo',
            'dong',
            'son',
            'hai',
            'dai',
            'dong',
            'nhat',
            'nhu',
            'kha',
            'quang',
            'binh',
            'truc',
            'sen',
            'lien',
            'dao',
            'huynh',
            'ho',
            'phan',
            'vu',
            'dang',
            'cao',
            'ly',
            'trinh',

            'an',
            'bao',
            'binh',
            'chau',
            'chi',
            'dao',
            'dien',
            'duc',
            'duy',
            'giang',
            'ha',
            'hai',
            'han',
            'hieu',
            'hoa',
            'hoang',
            'huy',
            'huyen',
            'khanh',
            'kien',
            'khoa',
            'kiet',
            'lam',
            'lan',
            'linh',
            'long',
            'ly',
            'mai',
            'minh',
            'my',
            'nga',
            'ngan',
            'ngoc',
            'nguyen',
            'nhan',
            'nhat',
            'nhung',
            'phat',
            'phong',
            'phuc',
            'phuong',
            'quang',
            'quyen',
            'son',
            'tai',
            'tan',
            'thanh',
            'thao',
            'thien',
            'thinh',
            'thu',
            'thuy',
            'tien',
            'toan',
            'trang',
            'tri',
            'trung',
            'tuan',
            'tung',
            'uyen',
            'van',
            'vi',
            'vinh',
            'vy',
            'xuan',
            'yen',
            'y',

            'anh',
            'bao',
            'chau',
            'chi',
            'dat',
            'duy',
            'giang',
            'ha',
            'han',
            'hieu',
            'hoa',
            'hoang',
            'huy',
            'khanh',
            'kien',
            'khoa',
            'kiet',
            'lam',
            'lan',
            'linh',
            'long',
            'mai',
            'minh',
            'my',
            'ngan',
            'ngoc',
            'nhan',
            'nhat',
            'nhung',
            'phat',
            'phong',
            'phuc',
            'phuong',
            'quang',
            'quyen',
            'son',
            'tai',
            'thanh',
            'thao',
            'thien',
            'thinh',
            'thu',
            'tien',
            'toan',
            'trang',
            'tri',
            'trung',
            'tuan',
            'tung',
            'uyen',
            'vinh',
            'vy',
            'xuan',

            'an',
            'binh',
            'cuong',
            'dung',
            'hiep',
            'loc',
            'phu',
            'sang',
            'son',
            'tai',
            'thang',
            'thien',
            'thu',
            'tien',
            'tri',
            'trung',
            'tu',
            'viet',
            'vinh',
            'bao',
            'duy',
            'khang',
            'kiet',
            'lam',
            'minh',
            'nghia',
            'phat',
            'quoc',
            'son',
            'thanh',
            'thien',
            'thinh',
            'trung',
            'tuan',
            'hieu',
            'loc',
            'phuc',

            'anh',
            'binh',
            'chau',
            'dat',
            'duc',
            'giang',
            'ha',
            'hanh',
            'hieu',
            'hoa',
            'huy',
            'khanh',
            'kien',
            'khoa',
            'kiet',
            'lam',
            'lan',
            'linh',
            'long',
            'mai',
            'minh',
            'my',
            'ngan',
            'ngoc',
            'nhan',
            'nhat',
            'nhung',
            'phat',
            'phong',
            'phuc',
            'phuong',
            'quang',
            'quyen',
            'son',
            'tai',
            'thanh',
            'thao',
            'thien',
            'thinh',
            'thu',
            'tien',
            'toan',
            'trang',
            'tri',
            'trung',
            'tuan',
            'tung',
            'uyen',
            'vinh',
            'vy',
            'xuan',
            'yen',
            'y',

            'an',
            'bao',
            'binh',
            'cuong',
            'dung',
            'hiep',
            'loc',
            'phu',
            'sang',
            'son',
            'tai',
            'thang',
            'thien',
            'thu',
            'tien',
            'tri',
            'trung',
            'tu',
            'viet',
            'vinh',
            'khoa',
            'kiet',
            'khang',
            'nghia',
            'phat',
            'quoc',
            'thanh',
            'thinh',
            'trung',
            'tuan',
            'hieu',
            'minh',
            'duy',
            'hoang',
            'quang',
            'lam',
            'lan',
            'linh',
            'mai',
            'ngoc',
            'phuong',
            'thao',
            'vy',

            'anh',
            'bao',
            'binh',
            'chau',
            'chi',
            'dat',
            'duc',
            'giang',
            'ha',
            'han',
            'hieu',
            'hoa',
            'huy',
            'khanh',
            'kien',
            'khoa',
            'kiet',
            'lam',
            'lan',
            'linh',
            'long',
            'mai',
            'minh',
            'my',
            'ngan',
            'ngoc',
            'nhan',
            'nhat',
            'nhung',
            'phat',
            'phong',
            'phuc',
            'phuong',
            'quang',
            'quyen',
            'son',
            'tai',
            'thanh',
            'thao',
            'thien',
            'thinh',
            'thu',
            'tien',
            'toan',
            'trang',
            'tri',
            'trung',
            'tuan',
            'tung',
            'uyen',
            'vinh',
            'vy',
            'xuan',
            'thuan',
            'phuoc',
            'hanh',
            'tam',
            'danh',
            'tu',
            'binh',
            'phong',
            'huy',
            'kha',
            'loc',
            'phuc',
            'an',
            'duy',
            'thien',
            'khang',
            'khanh',
            'bao',
            'nghi',
            'thanh',
            'nhu',
            'thao',
            'truc',
            'vy',
            'uyen',
            'nga',
            'loan',
            'huyen',
            'chi',
            'mai',
            'lan',
            'tram',
            'dao',
            'ngan',
            'kim',
            'y',
            'tuyet',
            'hue',
            'thu',
            'han',
            'phuong',
            'minh',
            'son',
            'long',
            'tien',
            'tri',
            'quang',
            'duc',
            'hieu',
            'vinh',


        ];
    }

    public function getRandomWords($pool, $length)
    {
        $keys = array_rand($pool, $length);
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $words = [];
        foreach ($keys as $key) {
            $words[] = $pool[$key];
        }
        return $words;
    }

    private function normalizeName($string)
    {
        // bỏ dấu tiếng Việt
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);

        // xóa ký tự lạ
        $string = preg_replace('/[^A-Za-z0-9 ]/', '', $string);

        // viết hoa toàn bộ
        $string = strtoupper($string);

        return trim($string);
    }
}
