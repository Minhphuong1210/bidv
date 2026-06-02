<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
class AuthController extends Controller
{
    /**
     * Cái này để login cho admin thôi còn user sẽ trên website rồi
     *  B1: Tạo link Telegram
     */

    public function showLogin()
    {
//        dd(
//            Auth::guard('web')->check(),
//            Auth::check()
//        );


        return view('auth.login');
    }

    public function redirectToTelegram()
    {
        // chống spam (Giới hạn 3 lần/phút mỗi IP)
        $ip = request()->ip();
        $cacheKey = 'login_token_spam_' . $ip;
        $count = Cache::get($cacheKey, 0);

        if ($count >= 3) {
            return response()->json(['error' => 'Bạn thao tác quá nhanh, vui lòng đợi 1 phút.'], 429);
        }
        Cache::put($cacheKey, $count + 1, 60);

        // System-wide safety (optional, kept from original)
        if (LoginToken::where('created_at', '>', now()->subMinute())->count() > 10) {
            return response()->json(['error' => 'Hệ thống đang bận, vui lòng thử lại sau.'], 429);
        }

        $token = Str::random(40);

        LoginToken::create([
            'token' => $token,
            'expired_at' => now()->addMinutes(5),
        ]);

        return response()->json([
            'link' => "https://t.me/mony_ve_bot?start=$token",
            'token' => $token,
        ]);
    }

    /**
     *  B2: Bot gọi về đây để login
     */
    public function handleLogin(Request $request)
    {
        $token = $request->token;
        $telegram_id = $request->telegram_id;

        $login = LoginToken::where('token', $token)
            ->where('used', false)
            ->where('expired_at', '>', now())
            ->first();

        if (!$login) {
            return response()->json(['error' => 'Token không hợp lệ']);
        }

        // 1. tạo hoặc update user
        $user = User::updateOrCreate(
            ['telegram_id' => $telegram_id],
            [
                'name' => $request->name ?? 'User Tele',
                'telegram_username' => $request->username ?? null,
                'telegram_last_login' => now(),
                'telegram_ip' => $request->ip(),
            ]
        );

        // 2. UPDATE TOKEN (QUAN TRỌNG)
        $login->update([
            'used' => true,
            'user_id' => $user->id, // 🔥 thêm dòng này
        ]);

        Auth::login($user);

        return response()->json([
            'success' => true,
        ]);
    }
    /**
     *  B3: check login (frontend gọi)
     */
    public function checkLogin(Request $request)
    {
        $token = $request->token;

//        Log::info(['token' => $token]);

        $login = LoginToken::where('token', $token)
            ->where('used', true)
            ->whereNotNull('user_id')
            ->first();

        if ($login) {
            $user = User::find($login->user_id);

            Auth::login($user);

            return response()->json([
                'logged_in' => true,
            ]);
        }

        return response()->json([
            'logged_in' => false,
        ]);
    }

    /**
     *  logout
     */
    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}
