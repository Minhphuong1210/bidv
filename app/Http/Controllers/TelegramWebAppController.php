<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TelegramWebAppController extends Controller
{
    /*
     * Làm login trên tele luôn k cần phải trên website nữa
     *
     *
     * **/


    public function login(Request $request)
    {

        $initData = $request->initData;




        parse_str($initData, $data);
        $telegramUser = json_decode($data['user'], true);

        //        dd($initData, $data);


        if (!$telegramUser) {
            return response()->json(['success' => false], 400);
        }


        if (abs(time() - $data['auth_date']) > 300) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập hết hạn'
            ], 403);
        }

        $user = User::where('telegram_id', $telegramUser['id'])->first();



        if (!$user) {
            $user = User::create([
                'telegram_id' => $telegramUser['id'],
                'name' => $telegramUser['first_name'] ?? 'User',
                'telegram_username' => $telegramUser['username'] ?? null,
                'telegram_first_name' => $telegramUser['first_name'] ?? null,
                'telegram_last_name' => $telegramUser['last_name'] ?? null,
                'telegram_language' => $telegramUser['language_code'] ?? null,
                'telegram_is_bot' => $telegramUser['is_bot'] ?? 0,
                'telegram_last_login' => now(),
                'telegram_ip' => $request->ip(),
                'is_approved' => 0,
                'login_count' => 1
            ]);
        } else {
            $user->update([
                'telegram_username' => $telegramUser['username'] ?? null,
                'telegram_first_name' => $telegramUser['first_name'] ?? null,
                'telegram_last_name' => $telegramUser['last_name'] ?? null,
                'telegram_language' => $telegramUser['language_code'] ?? null,
                'telegram_last_login' => now(),
                'telegram_ip' => $request->ip(),
                'login_count' => $user->login_count + 1
            ]);
        }



        if ($user->is_approved != 1) {
            return response()->json([
                'success' => false,
                'blocked' => true
            ]);
        }

        //        dd($user);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->save();
        //        dd(Auth::check());

        UserSession::create([
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        if (!$user->is_approved && $user->role !== 'admin') {
            $this->notifyAdminForApproval($user);
        }

        return response()->json([
            'success' => true,
            'approved' => $user->is_approved
        ]);
    }

    private function verifyTelegramData($initData)
    {
        if (!$initData) {
            Log::error('initData trống');
            return false;
        }

        parse_str($initData, $data);

        if (!isset($data['hash'])) {
            Log::error('Thiếu hash trong initData', ['data' => $data]);
            return false;
        }

        $hash = $data['hash'];
        unset($data['hash']);
        unset($data['signature']);

        ksort($data);

        $checkString = collect($data)
            ->map(fn($v, $k) => $k . '=' . $v)
            ->implode("\n");

        $botToken = config('services.telegram.token');
        $secretKey = hash_hmac('sha256', $botToken, "WebAppData", true);
        $calc = hash_hmac('sha256', $checkString, $secretKey);

        if (!hash_equals($hash, $calc)) {
            Log::error('Sai chữ ký Telegram', [
                'checkString' => $checkString,
                'hash_received' => $hash,
                'hash_calculated' => $calc,
                'bot_token_last_4' => substr($botToken, -4)
            ]);
            return false;
        }

        return true;
    }

    private function notifyAdminForApproval($user)
    {
        // Tránh spam notification liên tục cho cùng 1 user trong thời gian ngắn
        $cacheKey = 'notified_admin_approval_webapp_' . $user->id;
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, 300); // 5 phút notify 1 lần

        $notifier = app(\App\Http\Services\TelegramNotifier::class);
        $adminIds = $notifier->getAdminIds();

        $text = "🔔 <b>YÊU CẦU DUYỆT USER MỚI (WEBAPP)</b>\n\n"
            . "👤 User: <b>" . ($user->name ?? 'Người dùng mới') . "</b>\n"
            . "🆔 ID: <code>{$user->telegram_id}</code>\n"
            . "📛 Username: @" . ($user->telegram_username ?? 'N/A') . "\n"
            . "📅 Lúc: " . now()->format('H:i:s d/m/Y');

        $keyboard = [
            [
                ['text' => '✅ Duyệt', 'callback_data' => "approve_user_{$user->id}"],
                ['text' => '🚫 Khóa', 'callback_data' => "block_user_{$user->id}"]
            ]
        ];

        foreach ($adminIds as $adminId) {
            $notifier->sendMessageWithInlineKeyboard($adminId, $text, $keyboard);
        }
    }
}
