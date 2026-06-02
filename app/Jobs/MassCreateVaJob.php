<?php

namespace App\Jobs;

use App\Http\Services\TelegramNotifier;
use App\Http\Services\TelegramServiceSenDocument;
use App\Http\Services\VaService;
use App\Models\User;
use App\Models\VaAccount;
use App\Exports\VaExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class MassCreateVaJob implements ShouldQueue
{

    /*
     * Cái này cho admin
     *
     * */

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $merchantId;
    public $prefix;
    public $quantity;
    public $nameLength;
    public $userId;
    public $bankCode;
    public $accountLength;

    public function __construct($merchantId, $prefix, $quantity, $nameLength, $userId, $bankCode, $accountLength = 10)
    {
        $this->onQueue('admin-bulk');
        $this->merchantId = $merchantId;
        $this->prefix = $prefix;
        $this->quantity = $quantity;
        $this->nameLength = $nameLength;
        $this->userId = $userId;
        $this->bankCode = $bankCode;
        $this->accountLength = $accountLength;
    }

    public function handle(VaService $vaService, TelegramNotifier $notifier)
    {
        Log::info("[MassCreateJob] Started for User ID: {$this->userId}, Quantity: {$this->quantity}");

        $user = User::where('telegram_id', $this->userId)->first();

        // Generate names
        [$names, $displayNames] = $vaService->generateNames($this->prefix, $this->quantity, $this->nameLength);

        $created = [];
        $failed = [];

        // Save to DB
        foreach ($names as $name) {
            $vaNumber = $vaService->generateUniqueVaNumber($this->accountLength);

            $merchantName = config('hpay.name_prefix') . ' ' . $name;

            $va = VaAccount::create([
                'user_id' => $user ? $user->id : 1,
                'bank' => 'BIDV', // Force BIDV
                'merchant_name' => $merchantName,
                'va_number' => $vaNumber,
                'bank_full' => 'BIDV',
                'type' => 1,
                'amount' => 0,
                'amount_int' => 0,
                'bill_count' => 0,
                'status' => 1,
                'created_date' => now(),
                'created_by' => 0, // System/Job
                'fee_rate' => config('hpay.default_fee_rate', 8),
            ]);

            $va->update([
                'ma_don_hang' => 'DH' . now()->format('ymdHis') . $va->id . rand(100, 999),
            ]);

            $created[] = [
                'merchant_name' => $merchantName,
                'va_number' => $vaNumber,
            ];
        }

        $createdCount = count($created);
        if ($createdCount > 0 && $user && $user->telegram_id) {
            $notifier->sendMessage($user->telegram_id, "🎁 Admin vừa tạo hàng loạt cho bạn $createdCount tài khoản mới! Vui lòng kiểm tra trong mục 'Tạo Bánh'.");

            $fileName = 'va_' . time() . '.xlsx';
            $filePath = storage_path('app/' . $fileName);

            Excel::store(new VaExport($created, 'BIDV'), $fileName);

            $telegramDoc = app(TelegramServiceSenDocument::class);
            $telegramDoc->sendDocument($user->telegram_id, $filePath, $fileName);
        }

        Log::info("[MassCreateJob] Finished. Created: " . count($created) . ", Failed: " . count($failed));
    }
}
