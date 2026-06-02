<?php

namespace App\Jobs;

use App\Exports\VaExport;
use App\Http\Services\HPayOpenApiService;
use App\Http\Services\TelegramNotifier;
use App\Http\Services\TelegramServiceSenDocument;
use App\Http\Services\VaService;
use App\Models\User;
use App\Models\VaAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class BulkVaCreationJob implements ShouldQueue
{
    /*
     * Cái này cho bot (admin dùng /bulk)
     * - Gửi kết quả và file Excel về chatId của admin
     * - Gửi thông báo (không có file) cho user được tạo STK
     */

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 phút

    public $userId;      // DB id của user được tạo STK cho
    public $merchantId;  // merchant_id của ngân hàng (mid)
    public $prefix;
    public $quantity;
    public $wordCount;
    public $adminChatId; // chatId của admin đang thao tác trên bot
    public $bankCode;    // tên ngân hàng (VD: BIDV, VCB)
    public $accountLength; // Độ dài số VA

    public function __construct($userId, $merchantId, $prefix, $quantity, $wordCount, $adminChatId, $bankCode = '', $accountLength = 10)
    {
        $this->onQueue('bulk');
        $this->userId = $userId;
        $this->merchantId = $merchantId;
        $this->prefix = $prefix;
        $this->quantity = $quantity;
        $this->wordCount = $wordCount;
        $this->adminChatId = $adminChatId;
        $this->bankCode = $bankCode;
        $this->accountLength = $accountLength;
    }

    public function handle(VaService $vaService, TelegramNotifier $notifier)
    {
        Log::info("[BulkVaCreationJob] Start – userId={$this->userId}, qty={$this->quantity}");

        $user = User::find($this->userId);
        $bankName = $this->getBankName($this->merchantId);

        // 1. Tạo tên
        [$names] = $vaService->generateNames($this->prefix, $this->quantity, $this->wordCount);

        // 2. Tạo VA locally thay vì gọi API
        $created = [];
        foreach ($names as $name) {
            $vaNumber = $vaService->generateUniqueVaNumber($this->accountLength);
            $created[] = [
                'merchant_name' => $name,
                'va_number' => $vaNumber,
            ];
        }

        $failed = []; // local always success unless DB constraint fails

        // 3. Lưu DB
        $vaRecords = [];
        $now = now();
        $successCount = 0;

        foreach ($created as $item) {
            $vaRecords[] = [
                'user_id' => $this->userId,
                'va_number' => $item['va_number'],
                'merchant_name' => config('hpay.name_prefix') . ' ' . $item['merchant_name'],
                'bank' => $bankName,
                'bank_full' => $this->merchantId,
                'type' => 1,
                'amount' => 0,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'created_date' => $now,
                'created_by' => 0,
                'fee_rate' => config('hpay.default_fee_rate'),
                'ma_don_hang' => 'DH' . $now->format('ymdHis') . rand(1000, 9999),
            ];
        }

        if (!empty($vaRecords)) {
            try {
                VaAccount::insert($vaRecords);
                $successCount = count($vaRecords);
            } catch (\Exception $e) {
                Log::error("[BulkVaCreationJob] Batch insert failed: " . $e->getMessage());
                foreach ($vaRecords as $record) {
                    try {
                        VaAccount::create($record);
                        $successCount++;
                    } catch (\Exception $ex) {
                        Log::error("[BulkVaCreationJob] Single insert failed: " . $ex->getMessage());
                    }
                }
            }
        }

        // 4. Thông báo kết quả cho ADMIN + gửi file
        $resultMsg = "✅ <b>HOÀN TẤT TẠO TK HÀNG LOẠT</b>\n\n"
            . "👤 User: <code>{$this->userId}</code>" . ($user ? " (@{$user->telegram_username})" : "") . "\n"
            . "🏦 Ngân hàng: <b>{$bankName}</b>\n"
            . "📊 Thành công: <b>{$successCount}</b>\n"
            . "❌ Thất bại: <b>" . count($failed) . "</b>\n"
            . "⏰ " . now()->format('H:i:s d/m/Y');

        $notifier->sendMessage($this->adminChatId, $resultMsg);

        // 5. Xuất Excel và gửi cho ADMIN và USER
        if ($successCount > 0) {
            try {
                $fileName = 'bulk_va_' . $this->userId . '_' . time() . '.xlsx';
                $filePath = storage_path('app/' . $fileName);

                // Ghép prefix vào tên giống như trong DB
                $prefix = config('hpay.name_prefix', '');
                $exportData = array_map(function ($item) use ($prefix) {
                    return array_merge($item, [
                        'merchant_name' => trim($prefix . ' ' . ($item['merchant_name'] ?? '')),
                    ]);
                }, $created);

                Excel::store(new VaExport($exportData, $this->bankCode ?: $bankName), $fileName);

                $telegramDoc = app(TelegramServiceSenDocument::class);
                // Gửi cho admin
                $telegramDoc->sendDocument($this->adminChatId, $filePath, $fileName);

                // Gửi thông báo cho user được tạo
                if ($user && $user->telegram_id) {
                    $notifier->sendMessage(
                        $user->telegram_id,
                        "🎁 <b>Admin vừa tạo thêm {$successCount} STK cho bạn!</b>\n\n"
                        . "🏦 Ngân hàng: <b>{$bankName}</b>\n"
                        . "📋 Vui lòng kiểm tra trong mục <b>Tạo Bánh</b> để xem danh sách."
                    );
                    // Gửi file Excel cho user
                    $telegramDoc->sendDocument($user->telegram_id, $filePath, $fileName);
                }
            } catch (\Exception $e) {
                Log::error("[BulkVaCreationJob] Excel export failed: " . $e->getMessage());
            }
        }

        Log::info("[BulkVaCreationJob] Done – created={$successCount}, failed=" . count($failed));
    }

    private function getBankName($mid): string
    {
        foreach (config('hpay.banks', []) as $bank) {
            if (($bank['merchant_id'] ?? '') == $mid) {
                return $bank['name'] ?? $mid;
            }
        }
        return $mid;
    }
}
