<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Services\VaService;
use App\Models\BlockedIp;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VaController extends Controller
{
    protected VaService $service;

    public function __construct(VaService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $routeName = $request->route()->getName();
        $tab = 'tab-create';

        if ($routeName === 'user.profile') {
            $tab = 'tab-profile';
        } elseif ($routeName === 'user.withdraw') {
            $tab = 'tab-withdraw';
        }

        // Thống kê 24h
        $amount24h = Cache::remember("user_{$user->id}_amount24h", 60, function () use ($user) {
            return Transaction::where('user_id', $user->id)
                ->where('completion_time', '>=', Carbon::now()->subHours(24))
                ->sum('amount');
        });

        // Tổng đã rút (status = 1: Đã duyệt)
        $totalWithdrawn = Cache::remember("user_total_withdrawn_{$user->id}", 60, function () use ($user) {
            return Withdrawal::where('user_id', $user->id)
                ->where('status', 1)
                ->sum('amount');
        });

        $so_tien_con_lai = $user->diem;

        // Lịch sử rút tiền
        $withdrawals = Cache::remember("user_withdrawals_{$user->id}", 60, function () use ($user) {
            return Withdrawal::where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
        });

        // Các giao dịch chưa quy đổi (is_redeemed = 0)
        $unredeemedTransactions = Transaction::where('user_id', $user->id)
            ->where('is_redeemed', 0)
            ->orderBy('id', 'desc')
            ->get();

        return view('welcome', compact('amount24h', 'totalWithdrawn', 'so_tien_con_lai', 'tab', 'withdrawals', 'unredeemedTransactions'));
    }

    public function create(Request $request)
    {

        //        dd(123);

        $user = auth()->user();
        $ip = $request->ip();

        // 1. Chống spam: 5 requests / giây
        $now = time();
        $rateKey = 'va_rps_' . $user->id . '_' . $now;
        $count = Cache::get($rateKey, 0);

        if ($count >= 5) {
            $spamKey = 'spam_attempts_' . $ip;
            $attempts = Cache::increment($spamKey);
            Cache::put($spamKey, $attempts, 3600);

            if ($attempts >= 10) {
                $user->is_blocked = true;
                $user->save();
                BlockedIp::updateOrCreate(
                    ['ip_address' => $ip],
                    ['user_id' => $user->id, 'reason' => 'Spamming VA creation > 5req/s', 'blocked_at' => now()]
                );
                return response()->json(['status' => 'error', 'message' => 'Bạn đã bị chặn vĩnh viễn do hành vi spam.']);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Bạn thao tác quá nhanh, vui lòng đợi chút. (Lần ' . $attempts . '/10)',
            ]);
        }
        Cache::put($rateKey, $count + 1, 2); // Lưu 2s cho chắc

        $request->validate([
            'name' => 'required|string|max:100',
            'bank' => 'required|string|max:50',
        ]);

        $sanitizedName = strip_tags($request->name);
        $sanitizedBank = strip_tags($request->bank);
        $accountLength = (int) $request->input('account_length', 10);

        [$ok, $result] = $this->service->createVa(
            $sanitizedName,
            $sanitizedBank,
            $accountLength
        );

        return response()->json([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Tạo tài khoản thành công' : $result,
            'data' => $result,
        ]);
    }

    public function history()
    {
        return response()->json([
            'data' => $this->service->history(),
        ]);
    }

    public function createVaMultiple(Request $request)
    {
        //        dd(123);
        $user = auth()->user();
        $ip = $request->ip();

        // 1. Chống spam: 5 requests / giây (tương tự tạo đơn)
        $now = time();
        $rateKey = 'va_multi_rps_' . $user->id . '_' . $now;
        $count = Cache::get($rateKey, 0);

        if ($count >= 5) {
            $spamKey = 'spam_attempts_multi_' . $ip;
            $attempts = Cache::increment($spamKey);
            Cache::put($spamKey, $attempts, 3600);

            if ($attempts >= 10) {
                $user->is_blocked = true;
                $user->save();
                BlockedIp::updateOrCreate(
                    ['ip_address' => $ip],
                    ['user_id' => $user->id, 'reason' => 'Spamming Multi-STK creation > 5req/s', 'blocked_at' => now()]
                );
                return response()->json(['status' => 'error', 'message' => 'Bạn đã bị chặn vĩnh viễn do hành vi spam.']);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Bạn thao tác quá nhanh, vui lòng đợi chút. (Lần ' . $attempts . '/10)',
            ], 429);
        }
        Cache::put($rateKey, $count + 1, 2);

        $names = $request->names ?? [];
        $bank = $request->bank;
        $accountLength = (int) $request->input('account_length', 10);

        $so_luong = count($names);
        if ($so_luong > 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tối đa tạo 3 STK cùng lúc.',
            ]);
        }

        // dd($so_luong);

        $results = [];

        foreach ($names as $name) {

            $sanitizedName = strip_tags($name);
            $sanitizedBank = strip_tags($bank);

            [$ok, $result] = $this->service->createVa(
                $sanitizedName,
                $sanitizedBank,
                $accountLength
            );

            $results[] = [
                'name' => $sanitizedName,
                'status' => $ok,
                'data' => $result,
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Xử lý xong danh sách STK',
            'data' => $results,
        ]);
    }

}
