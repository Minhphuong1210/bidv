<?php

namespace App\Http\Controllers;

use App\Http\Services\TelegramNotifier;
use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramNotifier $notifier;

    public function __construct(TelegramNotifier $notifier)
    {
        $this->notifier = $notifier;
    }

    public function handle(Request $request)
    {
        // ===== GLOBAL LIMIT (300 req/min) =====
        $globalKey = 'tg_global_webhook_limit';
        $current = Cache::get($globalKey, 0);
        if ($current >= 300) {
            return $this->ok();
        }
        Cache::put($globalKey, $current + 1, 60);

        $update = $request->all();

        // ===== CALLBACK QUERY (nút bấm) =====
        if (isset($update['callback_query'])) {
            return $this->handleCallbackQuery($update['callback_query']);
        }

        if (!isset($update['message'])) {
            return $this->ok();
        }

        $message = $update['message'];
        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'] ?? null;
        $telegramId = $message['from']['id'] ?? null;

        if (!$chatId || !$telegramId) {
            return $this->ok();
        }

        // ===== SPAM BLOCK =====
        if (Cache::has("tg_blocked_chat_$chatId")) {
            return $this->ok();
        }

        $spamKey = "tg_spam_cnt_$chatId";
        $spamCount = Cache::get($spamKey, 0) + 1;
        Cache::put($spamKey, $spamCount, 1);

        if ($spamCount > 5) {
            Cache::put("tg_blocked_chat_$chatId", true, 3600);
            Log::warning("Spam ChatID: $chatId");
            return $this->ok();
        }

        // ===== /start (ưu tiên, không chặn) =====
        if (str_starts_with($text, '/start')) {
            return $this->handleStart($message, $text);
        }

        // ===== ADMIN CHECK (tất cả lệnh còn lại chỉ admin) =====
        $adminIds = Cache::remember(
            'admin_telegram_ids',
            600,
            fn() =>
            User::where('role', 'admin')->whereNotNull('telegram_id')->pluck('telegram_id')->toArray()
        );

        if (!in_array($telegramId, $adminIds)) {
            return $this->ok();
        }

        // ===== COMMANDS =====
        if ($text === '/help') {
            $this->notifier->sendMessage(
                $chatId,
                "🛠 <b>DANH SÁCH LỆNH ADMIN</b>\n\n"
                . "/start - Khởi động bot\n"
                . "/bc {nội dung} - Gửi thông báo toàn hệ thống\n"
                . "/sllauto - Quy trình tạo VA hàng loạt\n"
                . "/allsd - Xuất file Excel user có số dư\n"
                . "/resetpoints - Reset số dư toàn hệ thống về 0\n"
                . "/ban {ID/TeleID} - Khóa user (vô hiệu hóa tài khoản)\n"
                . "/uban {ID/TeleID} - Bỏ chặn (unblock) user và IP\n"
                . "/cancel - Hủy quy trình đang thực hiện\n"
                . "/help - Xem danh sách lệnh"
            );
            return $this->ok();
        }

        if (str_starts_with($text, '/bc ')) {
            $msg = trim(substr($text, 4));
            if (!$msg) {
                $this->notifier->sendMessage($chatId, "⚠️ Vui lòng nhập nội dung. VD: /bc Xin chào tất cả user");
                return $this->ok();
            }
            \App\Jobs\BroadcastNotificationJob::dispatch($msg);
            $this->notifier->sendMessage($chatId, "✅ Đã đưa thông báo vào hàng đợi gửi cho toàn bộ user.");
            return $this->ok();
        }

        if ($text === '/allsd') {
            $this->handleExportPoints($chatId);
            return $this->ok();
        }

        if ($text === '/resetpoints') {
            $this->handleResetPointsConfirm($chatId);
            return $this->ok();
        }

        if (str_starts_with($text, '/uban ')) {
            $targetId = trim(str_replace('/uban ', '', $text));
            $this->handleUnblock($chatId, $targetId);
            return $this->ok();
        }

        if (str_starts_with($text, '/ban ')) {
            $targetId = trim(str_replace('/ban ', '', $text));
            $this->handleBlockManual($chatId, $targetId);
            return $this->ok();
        }

        if ($text === '/sllauto') {
            $this->startBulkFlow($chatId);
            return $this->ok();
        }

        if ($text === '/cancel') {
            Cache::forget("bulk_state_$chatId");
            $this->notifier->sendMessage($chatId, "❌ Đã hủy quy trình.");
            return $this->ok();
        }

        // ===== TRẠNG THÁI FLOW =====
        $state = Cache::get("bulk_state_$chatId");
        if ($state) {
            $this->processBulkFlow($chatId, $text, $state);
        }

        return $this->ok();
    }

    // ==========================================
    // /start handler
    // ==========================================
    private function handleStart($message, $text)
    {
        $chatId = $message['chat']['id'];
        $telegramId = $message['from']['id'];
        $token = trim(str_replace('/start', '', $text));

        if ($token) {
            $this->handleLogin($message, $token);
            return $this->ok();
        }

        // Mở bot không có token → hiện nội quy + nút mở app
        $user = User::firstOrCreate(
            ['telegram_id' => $telegramId],
            [
                'name' => $message['from']['first_name'] ?? 'User',
                'telegram_username' => $message['from']['username'] ?? null,
                'telegram_first_name' => $message['from']['first_name'] ?? null,
                'telegram_last_name' => $message['from']['last_name'] ?? null,
                'is_approved' => 0,
            ]
        );

        $rules = "📜 <b>QUY ĐỊNH SỬ DỤNG</b>\n\n"
            . "🚫 Cấm lừa đảo dưới mọi hình thức\n"
            . "🚫 Không chia sẻ tài khoản\n"
            . "🚫 Không phát tán thông tin nội bộ\n"
            . "⚠️ <b>Vi phạm:</b> Khóa tài khoản và giữ lại số dư.\n\n"
            . "📩 <i>Nhấn nút bên dưới để mở ứng dụng.</i>";

        $keyboard = [
            [
                ['text' => '🚀 Mở Ứng Dụng', 'web_app' => ['url' => config('app.url')]]
            ]
        ];

        if ($user->is_approved || $user->role === 'admin') {
            $this->notifier->sendMessageWithInlineKeyboard($chatId, "✅ Chào mừng trở lại!\n\n" . $rules, $keyboard);
        } else {
            $this->notifier->sendMessageWithInlineKeyboard($chatId, "👋 Chào mừng bạn!\n\n" . $rules . "\n\n⏳ Tài khoản của bạn đang chờ Admin phê duyệt.", $keyboard);
            $this->notifyAdminForApproval($user);
        }

        return $this->ok();
    }

    // ==========================================
    // Login handler (có token)
    // ==========================================
    private function handleLogin($message, $token)
    {
        $telegramUser = $message['from'];
        $telegramId = $telegramUser['id'];
        $chatId = $message['chat']['id'];

        Log::info("Telegram Login: token=$token, telegramId=$telegramId");

        $user = User::updateOrCreate(
            ['telegram_id' => $telegramId],
            [
                'telegram_username' => $telegramUser['username'] ?? null,
                'telegram_first_name' => $telegramUser['first_name'] ?? null,
                'telegram_last_name' => $telegramUser['last_name'] ?? null,
                'telegram_last_login' => now(),
                'login_count' => DB::raw('login_count + 1'),
            ]
        );

        $loginToken = LoginToken::where('token', $token)
            ->where('used', 0)
            ->where('expired_at', '>', now())
            ->first();

        if ($loginToken) {
            $loginToken->update([
                'used' => 1,
                'telegram_id' => $telegramId,
                'user_id' => $user->id,
            ]);

            if ($user->role === 'admin' || $user->is_approved) {
                $this->notifier->sendMessage($chatId, "✅ Đăng nhập thành công! Quay lại trình duyệt để tiếp tục.");
            } else {
                $this->notifier->sendMessage($chatId, "✅ Đăng nhập thành công! Tài khoản của bạn đang chờ Admin phê duyệt.");
                $this->notifyAdminForApproval($user);
            }
        } else {
            $this->notifier->sendMessage($chatId, "❌ Token không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.");
        }
    }

    // ==========================================
    // Notify admin for new user approval
    // ==========================================
    private function notifyAdminForApproval($user)
    {
        $cacheKey = 'notified_admin_approval_' . $user->id;
        if (Cache::has($cacheKey))
            return;
        Cache::put($cacheKey, true, 300);

        $adminIds = $this->notifier->getAdminIds();
        $tgLink = "<a href='tg://user?id={$user->telegram_id}'>{$user->name}</a>";
        $text = $this->notifier->formatUserApprovalText($user);

        $keyboard = [
            [
                ['text' => '✅ Duyệt', 'callback_data' => "approve_user_{$user->id}"],
            ]
        ];

        foreach ($adminIds as $adminId) {
            $this->notifier->sendMessageWithInlineKeyboard($adminId, $text, $keyboard);
        }
    }

    // ==========================================
    // Callback query handler
    // ==========================================
    private function handleCallbackQuery($callbackQuery)
    {
        $data = $callbackQuery['data'];
        $callbackId = $callbackQuery['id'];
        $chatId = $callbackQuery['message']['chat']['id'];
        $fromId = $callbackQuery['from']['id'];

        $admin = User::where('telegram_id', $fromId)->where('role', 'admin')->first();
        if (!$admin) {
            $this->notifier->answerCallbackQuery($callbackId, "⛔ Bạn không có quyền.");
            return $this->ok();
        }

        if (str_starts_with($data, 'approve_user_')) {
            $userId = str_replace('approve_user_', '', $data);
            $user = User::find($userId);
            if ($user) {
                $user->update(['is_approved' => 1]);
                $messageId = $callbackQuery['message']['message_id'];
                $this->notifier->editMessage(
                    $chatId,
                    $messageId,
                    $this->notifier->formatUserApprovalText($user, 'approved')
                );
                $this->notifier->answerCallbackQuery($callbackId, "Đã duyệt thành công!");
                $this->notifier->sendMessage($user->telegram_id, "🎉 Tài khoản của bạn đã được Admin duyệt! Bạn có thể sử dụng bot ngay bây giờ.");
            }

        } elseif (str_starts_with($data, 'block_user_')) {
            $userId = str_replace('block_user_', '', $data);
            $user = User::find($userId);
            if ($user) {
                $user->update(['is_blocked' => 1]);
                $messageId = $callbackQuery['message']['message_id'];
                $originalText = $callbackQuery['message']['text'] ?? '';

                $newText = "🚫 <b>ĐÃ KHÓA USER</b>\n\n" . $originalText;

                // If it's a User Approval message, use the specific formatter to keep it pretty
                if (str_contains($originalText, "YÊU CẦU DUYỆT USER") || str_contains($originalText, "ĐÃ DUYỆT USER")) {
                    $newText = $this->notifier->formatUserApprovalText($user, 'blocked');
                }

                $this->notifier->editMessage(
                    $chatId,
                    $messageId,
                    $newText
                );
                $this->notifier->answerCallbackQuery($callbackId, "Đã khóa user!");
            }

        } elseif (str_starts_with($data, 'approve_withdraw_')) {
            $withdrawalId = str_replace('approve_withdraw_', '', $data);
            $withdrawal = \App\Models\Withdrawal::find($withdrawalId);
            if ($withdrawal && in_array($withdrawal->status, [0, 'pending'])) {
                $withdrawal->status = 1;
                $withdrawal->save();

                Cache::forget('admin_total_withdrawn');
                Cache::forget('admin_total_pending');
                Cache::forget("user_total_withdrawn_{$withdrawal->user_id}");
                Cache::forget("user_withdrawals_{$withdrawal->user_id}");

                $messageId = $callbackQuery['message']['message_id'];
                $this->notifier->editMessage(
                    $chatId,
                    $messageId,
                    $this->notifier->formatWithdrawalText($withdrawal),
                    null
                );
                $this->notifier->answerCallbackQuery($callbackId, "Đã duyệt thành công!");

                $user = User::find($withdrawal->user_id);
                if ($user && $user->telegram_id) {
                    $this->notifier->sendMessage($user->telegram_id, "🎉 Yêu cầu rút tiền #{$withdrawalId} đã được duyệt và đang được ngân hàng xử lý!");
                }
            } else {
                $this->notifier->answerCallbackQuery($callbackId, "❌ Lệnh rút này đã được xử lý hoặc không tồn tại.");

                if ($withdrawal) {
                    $this->notifier->editMessage(
                        $chatId,
                        $callbackQuery['message']['message_id'],
                        $this->notifier->formatWithdrawalText($withdrawal),
                        null
                    );
                }


            }

        } elseif (str_starts_with($data, 'reject_withdraw_')) {
            $withdrawalId = str_replace('reject_withdraw_', '', $data);
            $withdrawal = \App\Models\Withdrawal::find($withdrawalId);
            if ($withdrawal && in_array($withdrawal->status, [0, 'pending'])) {
                $withdrawal->status = 2;
                $withdrawal->save();

                \App\Models\Transaction::where('withdrawal_id', $withdrawal->id)
                    ->update(['is_redeemed' => 0, 'withdrawal_id' => null]);

                $user = User::find($withdrawal->user_id);
                if ($user) {
                    $user->diem += $withdrawal->amount;
                    $user->save();
                    if ($user->telegram_id) {
                        $this->notifier->sendMessage($user->telegram_id, "❌ Yêu cầu rút tiền #{$withdrawalId} đã bị từ chối. Số dư đã được hoàn lại.");
                    }
                }

                Cache::forget('admin_total_pending');
                Cache::forget("user_withdrawals_{$withdrawal->user_id}");

                $messageId = $callbackQuery['message']['message_id'];
                $this->notifier->editMessage(
                    $chatId,
                    $messageId,
                    $this->notifier->formatWithdrawalText($withdrawal)
                );
                $this->notifier->answerCallbackQuery($callbackId, "Đã từ chối lệnh rút #{$withdrawalId}");
            } else {
                $this->notifier->answerCallbackQuery($callbackId, "❌ Lệnh rút này đã được xử lý hoặc không tồn tại.");
            }

        } elseif ($data === 'confirm_reset_points') {
            $this->notifier->answerCallbackQuery($callbackId, "⏳ Đang thực hiện Reset...");
            $messageId = $callbackQuery['message']['message_id'];
            $count = User::where('diem', '>', 0)->count();
            if ($count > 0) {
                User::where('diem', '>', 0)->update(['diem' => 0]);
                $this->notifier->editMessage(
                    $chatId,
                    $messageId,
                    "✅ <b>ĐÃ RESET SỐ DƯ</b>\n\n📊 Đã đưa số dư của <b>$count</b> user về 0."
                );
            } else {
                $this->notifier->editMessage($chatId, $messageId, "ℹ️ Không có user nào có số dư > 0.");
            }

        } elseif ($data === 'cancel_reset_points') {
            $this->notifier->answerCallbackQuery($callbackId, "Đã hủy.");
            $messageId = $callbackQuery['message']['message_id'];
            $this->notifier->editMessage($chatId, $messageId, "❌ <b>HỦY RESET SỐ DƯ</b>\n\nThao tác đã bị hủy.");

        } elseif (str_starts_with($data, 'bulk_bank_')) {
            // Chọn ngân hàng qua inline button trong bulk flow
            $bank = str_replace('bulk_bank_', '', $data);
            $allBanks = config('hpay.banks');

            if (!isset($allBanks[$bank])) {
                $this->notifier->answerCallbackQuery($callbackId, "❌ Ngân hàng không hợp lệ.");
                return $this->ok();
            }

            $state = Cache::get("bulk_state_$chatId");
            if (!$state || $state['step'] !== 'AWAITING_BANK') {
                $this->notifier->answerCallbackQuery($callbackId, "⚠️ Phiên đã hết hạn. Gõ /bulk để bắt đầu lại.");
                return $this->ok();
            }

            $this->notifier->answerCallbackQuery($callbackId, "✅ Đã chọn $bank");
            $messageId = $callbackQuery['message']['message_id'];
            $this->notifier->editMessage(
                $chatId,
                $messageId,
                "✅ Số lượng: <b>{$state['quantity']}</b>\n🏦 Ngân hàng: <b>$bank</b>\n\n⏳ Đang đưa vào hàng đợi..."
            );

            $this->dispatchBulkJob($chatId, $state, $bank);
        }

        return $this->ok();
    }

    // ==========================================
    // Export points (xuất file Excel)
    // ==========================================
    private function handleExportPoints($chatId)
    {
        $usersWithPoints = User::where('diem', '>', 0)->get();
        $count = $usersWithPoints->count();

        if ($count === 0) {
            $this->notifier->sendMessage($chatId, "ℹ️ Hiện không có user nào có số dư > 0.");
            return;
        }

        $this->notifier->sendMessage($chatId, "⏳ Đang xuất file cho <b>$count</b> user...");

        $fileName = 'balances_report_' . time() . '.xlsx';
        $filePath = storage_path('app/' . $fileName);
        \Maatwebsite\Excel\Facades\Excel::store(new \App\Exports\UserBalanceExport($usersWithPoints), $fileName);

        $telegramDoc = app(\App\Http\Services\TelegramServiceSenDocument::class);
        $telegramDoc->sendDocument($chatId, $filePath, $fileName);
    }

    // ==========================================
    // Unblock handler
    // ==========================================
    private function handleUnblock($chatId, $targetId)
    {
        $user = User::where('id', $targetId)->orWhere('telegram_id', $targetId)->first();
        if (!$user) {
            $this->notifier->sendMessage($chatId, "❌ Không tìm thấy user với ID hoặc Telegram ID: <code>$targetId</code>.");
            return;
        }

        // 1. Unblock in user table
        $user->update(['is_blocked' => 0]);

        // 2. Remove from blocked_ips table
        $blockedRecords = \App\Models\BlockedIp::where('user_id', $user->id)->get();
        foreach ($blockedRecords as $record) {
            Cache::forget('blocked_ip_' . $record->ip_address);
            $record->delete();
        }

        // 3. Clear cache keys
        Cache::forget('blocked_user_' . $user->id);
        Cache::forget('spam_attempts_' . $user->id);

        // Clear IP cache if user is logging in from somewhere (we can't know for sure here without a request)
        // But we can try to find their last IP from transactions if needed.

        $this->notifier->sendMessage($chatId, "✅ <b>ĐÃ BỎ CHẶN</b>\n\n👤 User: <b>{$user->telegram_username}</b>\n🆔 ID: <code>{$user->id}</code>\n🔗 Telegram ID: <code>{$user->telegram_id}</code>\n\nTài khoản hiện đã có thể truy cập Web và Bot.");
    }

    // ==========================================
    // Reset points (hỏi xác nhận)
    // ==========================================
    private function handleResetPointsConfirm($chatId)
    {
        $count = User::where('diem', '>', 0)->count();
        $total = User::where('diem', '>', 0)->sum('diem');

        if ($count === 0) {
            $this->notifier->sendMessage($chatId, "ℹ️ Không có user nào có số dư > 0.");
            return;
        }

        $text = "💰 <b>XÁC NHẬN RESET SỐ DƯ</b>\n\n"
            . "📊 Có <b>$count</b> user đang có số dư > 0.\n"
            . "💎 Tổng: <b>" . number_format($total) . " đ</b>\n\n"
            . "⚠️ <i>Hành động này sẽ đưa toàn bộ số dư về 0. Dùng /exportpoints để lưu trước.</i>";

        $keyboard = [
            [
                ['text' => '✅ Xác nhận Reset', 'callback_data' => 'confirm_reset_points'],
                ['text' => '❌ Hủy', 'callback_data' => 'cancel_reset_points'],
            ]
        ];

        $this->notifier->sendMessageWithInlineKeyboard($chatId, $text, $keyboard);
    }

    // ==========================================
    // Bulk flow (tạo STK hàng loạt)
    // ==========================================
    private function startBulkFlow($chatId)
    {
        Cache::put("bulk_state_$chatId", ['step' => 'AWAITING_USER_ID'], 600);
        $this->notifier->sendMessage(
            $chatId,
            "🚀 <b>BẮT ĐẦU TẠO STK HÀNG LOẠT</b>\n\n"
            . "Bước 1: Nhập <b>Telegram ID</b> của người dùng cần tạo STK.\n"
            . "(Gõ /cancel để hủy)"
        );
    }

    private function processBulkFlow($chatId, $text, $state)
    {
        switch ($state['step']) {

            case 'AWAITING_USER_ID':
                $targetUser = User::where('telegram_id', $text)->first();
                if (!$targetUser) {
                    $this->notifier->sendMessage($chatId, "❌ Không tìm thấy user với Telegram ID: <code>$text</code>. Vui lòng nhập lại hoặc /cancel.");
                    return;
                }
                $state['target_user_id'] = $targetUser->id;
                $state['target_telegram_id'] = $text;
                $state['step'] = 'AWAITING_WORD_COUNT';
                Cache::put("bulk_state_$chatId", $state, 600);

                $keyboard = [
                    'keyboard' => [
                        [['text' => '1 Từ'], ['text' => '2 Từ']],
                        [['text' => '3 Từ'], ['text' => '4 Từ']],
                    ],
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                ];
                $this->notifier->sendMessageWithKeyboard(
                    $chatId,
                    "✅ Đã chọn user: <b>@{$targetUser->telegram_username}</b>\n\nBước 2: Chọn <b>Số từ ngẫu nhiên</b> cho tên STK.",
                    $keyboard
                );
                break;

            case 'AWAITING_WORD_COUNT':
                $wordCount = (int) filter_var($text, FILTER_SANITIZE_NUMBER_INT);
                if ($wordCount < 1 || $wordCount > 4) {
                    $this->notifier->sendMessage($chatId, "❌ Vui lòng chọn từ 1 đến 4 từ.");
                    return;
                }
                $state['word_count'] = $wordCount;
                $state['step'] = 'AWAITING_QUANTITY';
                Cache::put("bulk_state_$chatId", $state, 600);

                $this->notifier->sendMessageWithKeyboard(
                    $chatId,
                    "✅ Đã chọn: <b>$wordCount từ</b>\n\nBước 3: Nhập <b>Số lượng STK</b> cần tạo.",
                    ['remove_keyboard' => true]
                );
                break;

            case 'AWAITING_QUANTITY':
                $qty = (int) $text;
                if ($qty <= 0) {
                    $this->notifier->sendMessage($chatId, "❌ Số lượng phải lớn hơn 0. Vui lòng nhập lại.");
                    return;
                }
                $state['quantity'] = $qty;
                $state['step'] = 'AWAITING_ACCOUNT_LENGTH';
                Cache::put("bulk_state_$chatId", $state, 600);

                $this->notifier->sendMessage(
                    $chatId,
                    "✅ Số lượng: <b>$qty</b>\n\nBước 4: Nhập <b>Độ dài số tài khoản (số)</b>. VD: 10"
                );
                break;

            case 'AWAITING_ACCOUNT_LENGTH':
                $accLen = (int) $text;
                if ($accLen < 6 || $accLen > 20) {
                    $this->notifier->sendMessage($chatId, "❌ Độ dài số tài khoản không hợp lệ (từ 6 đến 20 số).");
                    return;
                }
                $state['account_length'] = $accLen;
                $state['step'] = 'AWAITING_BANK';
                Cache::put("bulk_state_$chatId", $state, 600);

                // Inline keyboard cho ngân hàng
                $allBanks = config('hpay.banks');
                $inlineRows = [];
                $inRow = [];
                foreach (array_keys($allBanks) as $bankKey) {
                    $inRow[] = ['text' => "🏦 $bankKey", 'callback_data' => "bulk_bank_$bankKey"];
                    if (count($inRow) === 2) {
                        $inlineRows[] = $inRow;
                        $inRow = [];
                    }
                }
                if ($inRow)
                    $inlineRows[] = $inRow;

                $this->notifier->sendMessageWithInlineKeyboard(
                    $chatId,
                    "✅ Số lượng: <b>{$state['quantity']}</b> | Dài: <b>$accLen số</b>\n\nBước 5: Bấm chọn <b>Ngân hàng</b>:",
                    $inlineRows
                );
                break;

            case 'AWAITING_BANK':
                // Dự phòng nếu user gõ text thay vì bấm nút
                $bank = strtoupper(trim($text));
                $allBanks = config('hpay.banks');
                if (!isset($allBanks[$bank])) {
                    $list = implode(', ', array_keys($allBanks));
                    $this->notifier->sendMessage($chatId, "❌ Ngân hàng <b>$bank</b> không hợp lệ.\nDanh sách: <code>$list</code>\nHoặc hãy nhấn nút trên tin nhắn trước.");
                    return;
                }
                $this->dispatchBulkJob($chatId, $state, $bank);
                break;
        }
    }

    private function dispatchBulkJob($adminChatId, $state, $bank)
    {
        $allBanks = config('hpay.banks');
        $mid = $allBanks[$bank]['merchant_id'];

        \App\Jobs\BulkVaCreationJob::dispatch(
            $state['target_user_id'],
            $mid ?? 'BIDV', // Fallback internally if need
            '',
            $state['quantity'],
            $state['word_count'],
            $adminChatId,
            $bank,
            $state['account_length'] ?? 10
        );

        Cache::forget("bulk_state_$adminChatId");
        $this->notifier->sendMessage(
            $adminChatId,
            "🚀 <b>ĐÃ VÀO HÀNG ĐỢI</b>\n\n"
            . "👤 User TG: <code>{$state['target_telegram_id']}</code>\n"
            . "🏦 Ngân hàng: <b>$bank</b>\n"
            . "📊 Số lượng: <b>{$state['quantity']}</b>\n\n"
            . "📄 Kết quả và file Excel sẽ gửi sau khi hoàn tất."
        );
    }

    private function handleBlockManual($chatId, $targetId)
    {
        $user = User::where('id', $targetId)->orWhere('telegram_id', $targetId)->first();
        if (!$user) {
            $this->notifier->sendMessage($chatId, "❌ Không tìm thấy user với ID hoặc Telegram ID: <code>$targetId</code>.");
            return;
        }

        if ($user->role === 'admin') {
            $this->notifier->sendMessage($chatId, "❌ Không thể khóa tài khoản Admin.");
            return;
        }

        // 1. Block in user table
        $user->update(['is_blocked' => 1]);

        // 2. Clear cache keys
        Cache::forget('blocked_user_' . $user->id);

        $this->notifier->sendMessage($chatId, "✅ <b>ĐÃ KHÓA USER</b>\n\n👤 User: <b>{$user->telegram_username}</b>\n🆔 ID: <code>{$user->id}</code>\n🔗 Telegram ID: <code>{$user->telegram_id}</code>\n\nTài khoản hiện đã bị vô hiệu hóa truy cập Web và Bot.");
    }

    private function ok()
    {
        return response()->json(['status' => 'ok']);
    }
}