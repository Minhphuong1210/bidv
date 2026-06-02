<?php

namespace App\Http\Controllers\Admin;

use App\Exports\VaExport;
use App\Http\Controllers\Controller;
use App\Http\Services\HPayOpenApiService;
use App\Http\Services\TelegramServiceSenDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Transaction;


class AdminController extends Controller
{

    public function dashboard($tab = 'dashboard')
    {
        $searchTele = request('telegram_id');

        $from = request('from_date');
        $to = request('to_date');


        if (!$from || !$to) {
            $from = Carbon::today()->startOfDay();
            $to = Carbon::today()->endOfDay();
        } else {
            $from = Carbon::parse($from)->startOfDay();
            $to = Carbon::parse($to)->endOfDay();
        }

        $usersQuery = \App\Models\User::orderBy('id', 'desc');

        if ($searchTele) {
            $usersQuery->where(function ($q) use ($searchTele) {
                $q->where('telegram_id', 'like', "%$searchTele%")
                    ->orWhere('telegram_username', 'like', "%$searchTele%");
            });
        }

        $users = $usersQuery->get();

        $withdrawals = \App\Models\Withdrawal::with('user')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('id', 'desc')
            ->get();


        $totalUsers = \App\Models\User::count();

        $totalWithdrawn = \App\Models\Withdrawal::where('status', 1)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $totalPending = \App\Models\User::sum('diem');

        $currentBalances = \App\Models\User::sum('diem');

        $totalIn = Transaction::whereBetween('created_at', [$from, $to])->sum('amount');

        $totalOut = Transaction::whereBetween('created_at', [$from, $to])->sum('actual_amount');

        $block_ip = \App\Models\BlockedIp::all();


        $threshold = 900000;

        $telegram_proxies = \App\Models\Setting::where('key', 'TELEGRAM_PROXIES')->value('value');
        if (!$telegram_proxies) {
            $telegram_proxies = env('TELEGRAM_PROXIES', '');
        }

        // (tuỳ chọn) đếm số giao dịch
        $countAbove330 = \App\Models\Transaction::whereBetween('created_at', [$from, $to])
            ->where('amount', '>=', $threshold)
            ->count();

        $countBelow330 = \App\Models\Transaction::whereBetween('created_at', [$from, $to])
            ->where('amount', '<', $threshold)
            ->count();

        return view('admin.dashboard', compact(
            'users',
            'withdrawals',
            'totalUsers',
            'totalWithdrawn',
            'totalPending',
            'totalIn',
            'totalOut',
            'tab',
            'block_ip',
            'from',
            'to',
            'countAbove330',
            'countBelow330',
            'telegram_proxies',
        ));
    }

    public function massCreateVa(Request $request, HPayOpenApiService $hpayService, \App\Http\Services\VaService $vaService, \App\Http\Services\TelegramNotifier $notifier)
    {
        // 1. Lấy input
        $merchantId = $request->input('bank');
        $prefix = $request->input('prefix');
        $quantity = (int) $request->input('quantity');
        $nameLength = (int) $request->input('name_length', 4);
        $userId = $request->input('user_id');
        $accountLength = (int) $request->input('account_length', 10);
        $user = \App\Models\User::where('telegram_id', $userId)->first();

        // 2. Validate
        $validate = $this->validateMassCreate($merchantId, $prefix, $quantity, $nameLength);
        if ($validate !== true) {
            return response()->json(['status' => 'error', 'message' => $validate]);
        }

        // 3. Lấy MID
        $mid = $this->getMid($merchantId);
        //


        if (!$mid) {
            return response()->json(['status' => 'error', 'message' => 'Ngân hàng chưa được cấu hình.']);
        }

        // 4. Generate names
        [$names, $displayNames] = $vaService->generateNames($prefix, $quantity, $nameLength);



        // 5. Dispatch Job
        \App\Jobs\MassCreateVaJob::dispatch(
            $mid ?? 'BIDV', // fallback to BIDV
            $prefix,
            $quantity,
            $nameLength,
            $userId,
            $merchantId,
            $accountLength
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Yêu cầu tạo hàng loạt đã được đưa vào hàng đợi. Vui lòng kiểm tra kết quả trên Telegram sau ít phút.',
            'data' => [
                'target_user' => $userId,
                'quantity' => $quantity
            ],
        ]);
    }

    private function validateMassCreate($merchantId, $prefix, $quantity, &$nameLength)
    {
        if (!$merchantId || !$prefix || $quantity <= 0) {
            return 'Vui lòng nhập đầy đủ: Bank, Tên Prefix và Số lượng.';
        }

        if (!in_array($nameLength, [2, 3, 4])) {
            $nameLength = 4;
        }

        //        if ($quantity > 100) {
//            return 'Số lượng tạo 1 lần tối đa là 100 để tránh timeout.';
//        }

        return true;
    }
    private function getMid($merchantId)
    {


        $creds = config("hpay.banks.{$merchantId}");



        return $mid = $creds['merchant_id'] ?? null;
    }

    public function toggleApproval(Request $request, \App\Http\Services\TelegramNotifier $notifier)
    {
        $user = \App\Models\User::find($request->id);
        if ($user) {
            $user->is_approved = !$user->is_approved;
            $user->save();

            if ($user->is_approved && $user->telegram_id) {
                $notifier->sendMessage($user->telegram_id, "🎉 Tài khoản của bạn đã được Admin duyệt! Bạn đã có thể sử dụng bot.");
            }

            return response()->json(['status' => 'success', 'new_status' => $user->is_approved]);
        }
        return response()->json(['status' => 'error', 'message' => 'User not found']);
    }

    public function toggleBlock(Request $request)
    {
        $user = \App\Models\User::find($request->id);
        if ($user) {
            $user->is_blocked = !$user->is_blocked;
            $user->save();
            return response()->json(['status' => 'success', 'new_status' => $user->is_blocked]);
        }
        return response()->json(['status' => 'error', 'message' => 'User not found']);
    }

    public function deleteUser(Request $request)
    {
        $user = \App\Models\User::find($request->id);
        if ($user) {
            $user->delete();
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error', 'message' => 'User not found']);
    }

    public function approveWithdrawal(Request $request)
    {
        $withdrawal = \App\Models\Withdrawal::find($request->id);
        if ($withdrawal && ($withdrawal->status == 0 || $withdrawal->status === 'pending')) {
            $withdrawal->status = 1; // 1 = completed
            $withdrawal->save();

            Cache::forget('admin_total_withdrawn');
            Cache::forget('admin_total_pending');
            Cache::forget("user_total_withdrawn_{$withdrawal->user_id}");
            Cache::forget("user_withdrawals_{$withdrawal->user_id}");

            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error', 'message' => 'Withdrawal not found or already processed']);
    }

    public function rejectWithdrawal(Request $request)
    {
        $withdrawal = \App\Models\Withdrawal::find($request->id);
        if ($withdrawal && ($withdrawal->status == 0 || $withdrawal->status === 'pending')) {
            $withdrawal->status = 2; // 2 = rejected
            $withdrawal->save();

            // Set linked transactions back to unredeemed
            \App\Models\Transaction::where('withdrawal_id', $withdrawal->id)
                ->update([
                    'is_redeemed' => 0,
                    'withdrawal_id' => null,
                ]);

            // Refund the user's diem
            $user = \App\Models\User::find($withdrawal->user_id);
            if ($user) {
                $user->diem += $withdrawal->amount;
                $user->save();
            }

            Cache::forget('admin_total_pending');
            Cache::forget("user_withdrawals_{$withdrawal->user_id}");

            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error', 'message' => 'Withdrawal not found or already processed']);
    }

    public function getWithdrawalDetails(Request $request)
    {
        $withdrawal = \App\Models\Withdrawal::with('transactions')->find($request->id);
        if (!$withdrawal) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy lệnh rút'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'withdrawal' => $withdrawal,
                'transactions' => $withdrawal->transactions,
            ],
        ]);
    }

    public function exportWithdrawal($id)
    {
        $withdrawal = \App\Models\Withdrawal::with('transactions')->find($id);
        if (!$withdrawal) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy lệnh rút'], 404);
        }

        $transactions = $withdrawal->transactions;

        $filename = "admin_withdrawal_{$id}_transactions.csv";
        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        ];

        $columns = ['TX ID', ' Number', 'Amount (VND)', 'Actual Amount (VND)', 'Completion Time', 'Description'];

        $callback = function () use ($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            fputcsv($file, $columns);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->tx_id,
                    $tx->va_number,
                    $tx->amount,
                    $tx->actual_amount,
                    $tx->completion_time,
                    $tx->description,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function removeIp(Request $request)
    {
        $block_ip = \App\Models\BlockedIp::where('id', $request->id)->first();
        if (!$block_ip) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không tìm thấy id này',
            ]);
        }
        $block_ip->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Xóa thành công',
        ]);
    }

    public function taoTheoYeuCauCreate(
        Request $request,
        \App\Http\Services\VaService $vaService
    ) {
        $request->validate([
            'file' => 'required|file|mimes:txt,csv',
        ]);

        $userPrefix = $request->input('user_id');
        $bank = $request->input('bank');
        $accountLength = (int) $request->input('account_length', 10);

        if (!$bank || !$userPrefix) {
            return response()->json([
                'status' => 'error',
                'message' => 'Thiếu ngân hàng hoặc user prefix',
            ]);
        }

        $file = $request->file('file');
        $content = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $success = [];
        $fail = [];

        foreach ($content as $line) {

            $name = trim(strip_tags($line));

            if (!$name) {
                $fail[] = $line;
                continue;
            }

            try {
                [$ok, $result] = $vaService->createVa(
                    $name,
                    $bank,
                    $accountLength
                );

                if ($ok) {
                    $success[] = [
                        'merchant_name' => $name,
                        'va_number' => $result['va_number'] ?? null,
                        'bank' => $bank,
                    ];
                } else {
                    $fail[] = $name;
                }

            } catch (\Exception $e) {
                $fail[] = $name;
            }
        }
        session([
            'tao_theo_yeu_cau_export' => $success,
        ]);
        return response()->json([
            'status' => 'success',
            'data' => [
                'created_count' => count($success),
                'failed_count' => count($fail),
                'created_list' => $success,
                'failed_list' => $fail,
            ],
        ]);
    }

    public function getLatestWithdrawals(Request $request)
    {
        $afterId = (int) $request->input('after_id', 0);

        $withdrawals = \App\Models\Withdrawal::with('user')
            ->when($afterId > 0, fn($q) => $q->where('id', '>', $afterId))
            ->where('status', 0) // chỉ lệnh đang chờ duyệt
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'amount' => $w->amount,
                    'bank' => $w->bank,
                    'stk' => $w->stk,
                    'name' => $w->name,
                    'qr_code' => $w->qr_code,
                    'status' => $w->status,
                    'user_id' => $w->user_id,
                    'user_name' => $w->user ? ($w->user->name ?: $w->user->telegram_first_name) : null,
                    'user_tg' => $w->user ? $w->user->telegram_username : null,
                    'user_tg_id' => $w->user ? $w->user->telegram_id : null,
                    'created_at' => $w->created_at,
                ];
            });

        return response()->json(['status' => 'success', 'data' => $withdrawals]);
    }

    public function exportTaoTheoYeuCau()
    {

        $data = session('tao_theo_yeu_cau_export', []);

        if (empty($data)) {
            return back()->with('error', 'Không có dữ liệu export');
        }

        $filename = 'tao_va_' . time() . '.csv';

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        $callback = function () use ($data) {

            $file = fopen('php://output', 'w');

            // BOM UTF-8 cho Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['Tên tài khoản', 'Số TK', 'Ngân hàng']);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row['merchant_name'],
                    "\t" . $row['va_number'], // tránh Excel đổi số
                    $row['bank'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function sendBroadcast(Request $request)
    {
        $message = $request->input('message');
        if (!$message) {
            return response()->json(['status' => 'error', 'message' => 'Vui lòng nhập nội dung tin nhắn']);
        }

        \App\Jobs\BroadcastNotificationJob::dispatch($message);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã đưa thông báo vào hàng đợi gửi cho tất cả user.'
        ]);
    }

    public function saveProxySettings(Request $request)
    {
        $proxies = $request->input('proxies');
        
        \App\Models\Setting::updateOrCreate(
            ['key' => 'TELEGRAM_PROXIES'],
            ['value' => $proxies]
        );

        Cache::forget('telegram_proxies');

        return response()->json([
            'status' => 'success',
            'message' => 'Lưu cấu hình proxy thành công.'
        ]);
    }
}
