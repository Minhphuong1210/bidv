<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\WithdrawalTransactionsExport;
use App\Http\Services\TelegramServiceSenDocument;
class TelegramNotifier
{
    protected string $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.token');
    }

    private function send(string $chatId, string $message, $replyMarkup = null, $extraParams = []): void
    {
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $params = array_merge([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ], $extraParams);

            if ($replyMarkup) {
                $params['reply_markup'] = json_encode($replyMarkup);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $selectedProxy = 'Direct';
            // Rotating Proxy Logic
            $proxiesStr = \Illuminate\Support\Facades\Cache::remember('telegram_proxies', 60, fn() => \App\Models\Setting::where('key', 'TELEGRAM_PROXIES')->value('value') ?: env('TELEGRAM_PROXIES'));
            if ($proxiesStr) {
                $proxies = preg_split('/[\n\r,]+/', $proxiesStr);
                $proxies = array_filter(array_map('trim', $proxies));

                if (count($proxies) > 0) {
                    $selectedProxy = $proxies[array_rand($proxies)];

                    // Handle user:pass@host:port format
                    if (strpos($selectedProxy, '@') !== false) {
                        list($auth, $hostPort) = explode('@', $selectedProxy);
                        curl_setopt($ch, CURLOPT_PROXY, $hostPort);
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $auth);
                    } else {
                        curl_setopt($ch, CURLOPT_PROXY, $selectedProxy);
                    }
                }
            }

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                \Log::error("Telegram CURL error (Proxy: {$selectedProxy}): " . $err);
            } else {
                \Log::info("Telegram send success (Proxy: {$selectedProxy})");
            }
        } catch (\Exception $e) {
            \Log::error("Telegram send error: " . $e->getMessage());
        }
    }

    /**
     * Gửi cho user
     */
    public function sendToUser($chatId, $amount, $vaNumber, $txId, $description, $actualAmount, $merchantName = '')
    {

        $maskedVa = Str::mask($vaNumber, '*', 0, 12);

        // Masking merchant name based on VA_NAME_PREFIX
        $prefix = env('VA_NAME_PREFIX', '');
        if ($prefix && !empty($merchantName) && str_starts_with($merchantName, $prefix)) {
            $merchantName = '*' . substr($merchantName, strlen($prefix));
        }

        $msg =
            "🟢 <b>TIỀN VỀ</b>\n\n"
            . "👤 Tên : <b>" . ($merchantName ?: 'N/A') . "</b>\n"
            . "💳 STK: <code>{$maskedVa}</code>\n"
            . "💰 Số tiền: +" . number_format($amount) . " VND\n"
            . "💰 Thực nhận: +" . number_format($actualAmount) . " VND\n"
            . "📝 Nội dung: {$description}\n"
            . "🔑 TXID: <code>{$txId}</code>\n"
            . "⏰ " . date('H:i:s d/m/Y');

        $this->send($chatId, $msg);
    }

    /**
     * Gửi cho admin
     */
    public function sendToAdmin($data)
    {
        $adminIds = $this->getAdminIds();

        $merchantName = $data['merchant_name'] ?? 'N/A';
        if (!empty($data['telegram_id'])) {
            $merchantName = "<a href='tg://user?id={$data['telegram_id']}'>{$merchantName}</a>";
            if (!empty($data['telegram_username'])) {
                $merchantName .= " (@{$data['telegram_username']})";
            }
        }

        $msg =
            "🔔 <b>TIỀN VỀ HỆ THỐNG</b>\n\n"
            . "👤 Tên : <b>" . $merchantName . "</b>\n"
            . "👤 User ID: {$data['user_id']}\n"
            . "💳 STK: <code>{$data['va_number']}</code>\n"
            . "💰 +" . number_format($data['amount']) . " VND\n"
            . "💰 Thực nhận: +" . number_format($data['actual_amount']) . " VND\n"
            . "📝 {$data['description']}\n"
            . "🔑 TXID: <code>{$data['tx_id']}</code>\n"
            . "⏰ " . date('H:i:s d/m/Y');

        $keyboard = null;

        foreach ($adminIds as $chatId) {
            $this->send($chatId, $msg, $keyboard);
        }
    }
    public function getAdminIds(): array
    {
        return \App\Models\User::where('role', 'admin')
            ->whereNotNull('telegram_id')
            ->pluck('telegram_id')
            ->toArray();
    }

    public function sendMessage($chatId, $text): void
    {
        $this->send($chatId, $text);
    }

    public function sendMessageWithKeyboard($chatId, $text, $keyboard): void
    {
        $this->send($chatId, $text, $keyboard);
    }

    public function sendMessageWithInlineKeyboard($chatId, $text, $keyboard): void
    {
        $this->send($chatId, $text, ['inline_keyboard' => $keyboard]);
    }

    public function answerCallbackQuery($callbackId, $text = ''): void
    {
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/answerCallbackQuery";
            $params = [
                'callback_query_id' => $callbackId,
                'text' => $text,
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $selectedProxy = 'Direct';
            // Rotating Proxy Logic
            $proxiesStr = \Illuminate\Support\Facades\Cache::remember('telegram_proxies', 60, fn() => \App\Models\Setting::where('key', 'TELEGRAM_PROXIES')->value('value') ?: env('TELEGRAM_PROXIES'));
            if ($proxiesStr) {
                $proxies = preg_split('/[\n\r,]+/', $proxiesStr);
                $proxies = array_filter(array_map('trim', $proxies));

                if (count($proxies) > 0) {
                    $selectedProxy = $proxies[array_rand($proxies)];
                    if (strpos($selectedProxy, '@') !== false) {
                        list($auth, $hostPort) = explode('@', $selectedProxy);
                        curl_setopt($ch, CURLOPT_PROXY, $hostPort);
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $auth);
                    } else {
                        curl_setopt($ch, CURLOPT_PROXY, $selectedProxy);
                    }
                }
            }

            curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                \Log::error("Telegram answerCallbackQuery CURL error (Proxy: {$selectedProxy}): " . $err);
            }
        } catch (\Exception $e) {
            \Log::error("Telegram answerCallbackQuery error: " . $e->getMessage());
        }
    }

    public function sendToAdminWithdraw($withdrawal)
    {
        $adminIds = $this->getAdminIds();
        $msg = $this->formatWithdrawalText($withdrawal);

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '✅ Duyệt',
                        'callback_data' => 'approve_withdraw_' . $withdrawal->id
                    ],
                    [
                        'text' => '❌ Từ chối',
                        'callback_data' => 'reject_withdraw_' . $withdrawal->id
                    ]
                ]
            ]
        ];

        foreach ($adminIds as $chatId) {
            $this->send($chatId, $msg, $keyboard);
        }

        // Cũng gửi cho user (không có nút bấm)
        $withdrawalModel = \App\Models\Withdrawal::find($withdrawal->id);
        if ($withdrawalModel) {
            $user = $withdrawalModel->user;
            if ($user && $user->telegram_id) {
                $this->send($user->telegram_id, $msg);
            }
        }
    }

    /**
     * Định dạng nội dung tin nhắn rút tiền (Dùng chung cho cả gửi mới và khi sửa/duyệt)
     */
    public function formatWithdrawalText($withdrawal): string
    {
        $user = null;
        $withdrawalModel = null;

        if (is_object($withdrawal) && isset($withdrawal->id)) {
            $withdrawalModel = \App\Models\Withdrawal::find($withdrawal->id);
            if ($withdrawalModel) {
                $user = $withdrawalModel->user;
            }
        }

        $tgLink = $user && $user->telegram_id
            ? "<a href='tg://user?id={$user->telegram_id}'>{$withdrawal->name}</a>"
            : $withdrawal->name;

        $totalWithdraw = 0;
        $actualTransfer = 0;
        $stkCount = 0;
        $billCount = 0;
        $payId = 'W' . str_pad($withdrawal->id, 6, '0', STR_PAD_LEFT);

        if ($withdrawalModel) {
            $transactions = $withdrawalModel->transactions;
            $totalWithdraw = $transactions->sum('amount');
            $actualTransfer = $transactions->sum('actual_amount');
            $stkCount = $transactions->unique('va_number')->count();
            $billCount = $transactions->count();
        } else {
            $actualTransfer = $withdrawal->amount;
            $totalWithdraw = $withdrawal->amount;
        }

        $fixedWithdrawalFee = $withdrawal->fee ?? 0;
        $totalFee = ($totalWithdraw - $actualTransfer) + $fixedWithdrawalFee;
        $actualTransfer = $actualTransfer - $fixedWithdrawalFee;

        return "🔔 <b>YÊU CẦU RÚT TIỀN MỚI!</b>\n\n"
            . "🔑 Pay ID: <code>{$payId}</code>\n"
            . "👤 User: <b>{$tgLink}</b>" . ($user && $user->telegram_username ? " (@{$user->telegram_username})" : "") . "\n"
            . "💰 Tổng rút: <b>" . number_format($totalWithdraw) . " đ</b>\n"
            . "🎟️ Phí rút (V): <b>" . number_format($totalWithdraw - ($actualTransfer + $fixedWithdrawalFee)) . " đ</b>\n"
            . ($fixedWithdrawalFee > 0 ? "🎟️ Phí rút (CĐ): <b>" . number_format($fixedWithdrawalFee) . " đ</b>\n" : "")
            . "💸 Thực chuyển: <b>" . number_format($actualTransfer) . " đ</b>\n\n"
            . "📊 Số STK: <b>{$stkCount}</b> | 📄 Bill: <b>{$billCount}</b>\n\n"
            . "⚠️ <i>Tiền đã được trừ trong hệ thống.</i>";
    }

    // 3. Tự động xuất file giao dịch và gửi cho Admin như yêu cầu
    public function autoSendWithdrawalExport($withdrawal)
    {
        $withdrawalModel = \App\Models\Withdrawal::find($withdrawal->id);
        if (!$withdrawalModel)
            return;

        $adminIds = $this->getAdminIds();
        $user = $withdrawalModel->user;
        $payId = 'W' . str_pad($withdrawal->id, 6, '0', STR_PAD_LEFT);

        try {
            $transactions = $withdrawalModel->transactions()->with('vaAccount')->get();
            if ($transactions->count() > 0) {
                $fileName = 'withdrawal_' . $withdrawal->id . '_tx_export_' . time() . '.xlsx';
                $filePath = storage_path('app/' . $fileName);

                Excel::store(new WithdrawalTransactionsExport($transactions), $fileName);

                $telegramDoc = app(TelegramServiceSenDocument::class);
                $stkCount = $transactions->unique('va_number')->count();
                $totalWithdraw = $transactions->sum('amount');
                $caption = "📁 Chi tiết rút tiền {$payId} | {$stkCount} STK | " . number_format($totalWithdraw) . " đ";

                foreach ($adminIds as $chatId) {
                    $telegramDoc->sendDocument($chatId, $filePath, "Giao-dich-rut-{$withdrawal->id}.xlsx", $caption);
                }

                if ($user && $user->telegram_id) {
                    $telegramDoc->sendDocument($user->telegram_id, $filePath, "Doi-soat-rut-tien-{$withdrawal->id}.xlsx", "📄 File đối soát lệnh rút {$payId} của bạn.");
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error exporting withdrawal transactions: " . $e->getMessage());
        }
    }

    /**
     * Định dạng nội dung tin nhắn phê duyệt user (Dùng chung cho cả gửi mới và khi duyệt/khóa)
     */
    public function formatUserApprovalText($user, $status = 'pending'): string
    {
        $header = "🔔 <b>YÊU CẦU DUYỆT USER MỚI</b>";
        if ($status === 'approved') {
            $header = "✅ <b>ĐÃ DUYỆT USER</b>";
        } elseif ($status === 'blocked') {
            $header = "🚫 <b>ĐÃ KHÓA USER</b>";
        }

        $tgLink = "<a href='tg://user?id={$user->telegram_id}'>{$user->name}</a>";

        return "$header\n\n"
            . "👤 User: <b>{$tgLink}</b>\n"
            . "🆔 Telegram ID: <code>{$user->telegram_id}</code>\n"
            . "📛 Username: @" . ($user->telegram_username ?? 'N/A') . "\n"
            . "📅 Lúc: " . now()->format('H:i:s d/m/Y');
    }

    public function editMessage(string $chatId, int $messageId, string $text, $replyMarkup = null): void
    {
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/editMessageText";
            $params = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if ($replyMarkup) {
                $params['reply_markup'] = json_encode($replyMarkup);
            } else {
                // If no markup specified, explicitly remove the current keyboard
                $params['reply_markup'] = json_encode(['inline_keyboard' => []]);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $selectedProxy = 'Direct';
            // Rotating Proxy Logic
            $proxiesStr = \Illuminate\Support\Facades\Cache::remember('telegram_proxies', 60, fn() => \App\Models\Setting::where('key', 'TELEGRAM_PROXIES')->value('value') ?: env('TELEGRAM_PROXIES'));
            if ($proxiesStr) {
                $proxies = preg_split('/[\n\r,]+/', $proxiesStr);
                $proxies = array_filter(array_map('trim', $proxies));

                if (count($proxies) > 0) {
                    $selectedProxy = $proxies[array_rand($proxies)];
                    if (strpos($selectedProxy, '@') !== false) {
                        list($auth, $hostPort) = explode('@', $selectedProxy);
                        curl_setopt($ch, CURLOPT_PROXY, $hostPort);
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $auth);
                    } else {
                        curl_setopt($ch, CURLOPT_PROXY, $selectedProxy);
                    }
                }
            }

            curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                \Log::error("Telegram editMessage CURL error (Proxy: {$selectedProxy}): " . $err);
            }
        } catch (\Exception $e) {
            Log::error("Telegram editMessage error: " . $e->getMessage());
        }
    }
}
