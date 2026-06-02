<?php

namespace App\Modules\BIDV\Services;

use App\Modules\BIDV\Helpers\BIDVCryptoHelper;
use App\Models\VaAccount;
use App\Models\BotUser;
use App\Models\Transaction;
use App\Http\Services\TelegramNotifier as TelegramService;
use App\Services\UserManagerService;
use Illuminate\Support\Facades\Log;

class BIDVServerService
{
    /**
     * Xử lý yêu cầu Vấn tin (getbill) từ BIDV
     */
    public function getBill(array $payload)
    {

Log::info('[BIDV Server] GetBill request received', [
        'time' => now()->format('Y-m-d H:i:s'),
        'payload' => $payload,
    ]);

        $customerId = $payload['customerId'] ?? '';
        $serviceId = $payload['serviceId'] ?? '';
        $checksum = $payload['checksum'] ?? '';

        $secretCode = config('bidv.secret_code');

        // Verify Checksum: base64_encode(hash('sha256', "{secret_code}+{service_id}+{customer_id}", true))
        $dataToHash = "{$secretCode}+{$serviceId}+{$customerId}";
        $expectedChecksum = BIDVCryptoHelper::getBase64SHA256($dataToHash);

        if ($checksum !== $expectedChecksum) {
            Log::warning("[BIDV Server] GetBill checksum mismatch. Expected: {$expectedChecksum}, Got: {$checksum}");
            return [
                'result_code' => '004',
                'result_desc' => 'Checksum không hợp lệ'
            ];
        }

        // Query database to find the VA account (checking both exact full number or suffix code)
        $va = VaAccount::where('va_number', $customerId)
            ->orWhere('va_number', config('bidv.service_code') . $customerId)
            ->first();

        if (!$va) {
            Log::warning("[BIDV Server] GetBill VA account not found: {$customerId}");
            return [
                'result_code' => '011',
                'result_desc' => 'Mã khách hàng không đúng'
            ];
        }

        // Return standard BIDV GetBill success response allowing any dynamic amount (type = 2)
        return [
            'result_code' => '000',
            'result_desc' => 'success',
            'customer_id' => $customerId,
            'customer_name' => $va->merchant_name,
            'customer_addr' => 'Hanoi, Vietnam',
            'type' => '2', // 2: Cho phép nhập số tiền chuyển khoản tùy ý
            'total_amount' => '0',
            'data' => [
                [
                    'period' => 'Nap tien',
                    'data' => [
                        [
                            'bill_id' => $customerId,
                            'amount' => '0',
                            'remark' => 'Thanh toan don hang  ' . $va->merchant_name
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Xử lý yêu cầu Gạch nợ (paybill) từ BIDV
     */
    public function payBill(array $payload)
    {

 Log::info('[BIDV Server] PayBill request received', [
        'time' => now()->format('Y-m-d H:i:s'),
        'payload' => $payload,
    ]);

        $transId = $payload['transId'] ?? '';
        $transDate = $payload['transDate'] ?? '';
        $customerId = $payload['customerId'] ?? '';
        $billId = $payload['billId'] ?? '';
        $amount = (int)($payload['amount'] ?? 0);
        $checksum = $payload['checksum'] ?? '';

        $secretCode = config('bidv.secret_code');

        // Verify Checksum: base64_encode(hash('sha256', "{secret_code}+|{trans_id}+{amount}", true))
        $dataToHash = "{$secretCode}+|{$transId}+{$amount}";
        $expectedChecksum = BIDVCryptoHelper::getBase64SHA256($dataToHash);

        if ($checksum !== $expectedChecksum) {
            Log::warning("[BIDV Server] PayBill checksum mismatch. Expected: {$expectedChecksum}, Got: {$checksum}");
            return [
                'result_code' => '004',
                'result_desc' => 'Checksum không hợp lệ'
            ];
        }

        // Query database to find the VA account
        $va = VaAccount::where('va_number', $customerId)
            ->orWhere('va_number', config('bidv.service_code') . $customerId)
            ->first();

        if (!$va) {
            Log::warning("[BIDV Server] PayBill VA not found: {$customerId}");
            return [
                'result_code' => '021',
                'result_desc' => 'Mã hóa đơn không tồn tại'
            ];
        }

        $userId = $va->user_id;
        $feeRate = $va->fee_rate ?? config('hpay.default_fee_rate', 8);
        $actualAmount = $amount - intval($amount * $feeRate / 100);

        // Check duplicate transaction
        if (Transaction::isDuplicate($transId, $va->va_number, $amount)) {
            Log::info("[BIDV Server] Duplicate transaction detected: {$transId} for VA {$va->va_number}");
            return [
                'result_code' => '000',
                'result_desc' => 'success'
            ];
        }

        // Save Transaction
        $timeStr = now()->format('Y-m-d H:i:s');
        try {
            Transaction::create([
                'tx_id' => $transId,
                'va_number' => $va->va_number,
                'amount' => $amount,
                'actual_amount' => $actualAmount,
                'fee_rate' => $feeRate,
                'completion_time' => $timeStr,
                'user_id' => $userId,
                'description' => "Nạp tiền BIDV Direct - Mã GD: " . $transId,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                Log::info("[BIDV Server] Duplicate transaction database hit: {$transId}");
                return [
                    'result_code' => '000',
                    'result_desc' => 'success'
                ];
            }
            throw $e;
        }

        // Credit balance
        $userManager = app(UserManagerService::class);
        $userManager->addUserBalance($userId, $actualAmount, 1);
        $userManager->addVaBalance($va->va_number, $actualAmount, 1);

        // Send notifications
        $telegram = app(TelegramService::class);

        $msg = "🟢 <b>TIỀN VỀ (BIDV)!</b> 🟢\n\n"
            . "💳 <b>Số tài khoản:</b> <code>{$va->va_number}</code>\n"
            . "👤 <b>Chủ TK:</b> {$va->merchant_name}\n"
            . "🏦 <b>Ngân hàng:</b> BIDV\n\n"
            . "💰 <b>Số tiền:</b> +" . format_money($amount) . "\n"
            . "⏰ <b>Thời gian:</b> " . now()->format('H:i:s d/m/Y') . "\n"
            . "📝 <b>Nội dung:</b> Nạp tiền BIDV Direct\n\n"
            . "✅ <b>Thực nhận: +" . format_money($actualAmount) . "</b> (Đã trừ phí {$feeRate}%)";

        $telegram->sendMessage($userId, $msg, 'HTML');

        // Notify admins
        $adminIds = config('telegram.admin_ids', []);
        foreach ($adminIds as $adminId) {
            $adminMsg = "🔔 <b>THÔNG BÁO BIDV DIRECT</b>\n"
                . "💳 STK: <code>{$va->va_number}</code>\n"
                . "👤 Chủ TK: {$va->merchant_name}\n"
                . "💰 +" . format_money($amount) . "\n"
                . "✅ Thực nhận: +" . format_money($actualAmount) . " (Phí {$feeRate}%)\n"
                . "👤 User: " . user_link($userId) . "\n"
                . "📝 <b>Nội dung:</b> Nạp tiền BIDV Direct\n\n";

            try {
                $telegram->sendMessage($adminId, $adminMsg, 'HTML');
            } catch (\Exception $e) {
                // Ignore
            }
        }

        Log::info("[BIDV Server] Processed PayBill successfully: {$transId}");

        return [
            'result_code' => '000',
            'result_desc' => 'success'
        ];
    }
}
