<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Services\TelegramNotifier;
use App\Models\UserBankAccount;
use App\Models\Withdrawal;
use App\Jobs\SendTelegramNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UserController extends Controller
{

    protected TelegramNotifier $notifycationAdminWitdrawl;
    public function __construct(TelegramNotifier $notifycationAdminWitdrawl)
    {
        $this->notifycationAdminWitdrawl = $notifycationAdminWitdrawl;
    }

    public function getStats()
    {
        $user = Auth::user();
        $totalWithdrawn = Cache::remember("user_total_withdrawn_{$user->id}", 60, function () use ($user) {
            return Withdrawal::where('user_id', $user->id)->where('status', 1)->sum('amount');
        });
        $banks = Cache::remember("user_banks_{$user->id}", 60, function () use ($user) {
            return UserBankAccount::where('user_id', $user->id)->get();
        });

        return response()->json([
            'diem' => $user->diem,
            'total_withdrawn' => $totalWithdrawn,
            'banks' => $banks,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:user_bank_accounts,id',
            'name' => 'required|string|max:255',
            'bank' => 'required|string|max:255',
            'stk' => 'required|string|max:255|alpha_num',
            'qr_code' => 'nullable|image|max:5120',
        ]);

        $user = Auth::user();
        $id = $request->id;

        $qrCodePath = null;
        if ($request->hasFile('qr_code')) {
            $file = $request->file('qr_code');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/qrcodes'), $filename);
            $qrCodePath = '/uploads/qrcodes/' . $filename;
        }

        if ($id) {
            $bankAccount = UserBankAccount::where('id', $id)->where('user_id', $user->id)->first();
            if (!$bankAccount) {
                return response()->json(['status' => 'error', 'message' => 'Không tìm thấy tài khoản ngân hàng!'], 404);
            }

            $bankAccount->update([
                'name' => strip_tags($request->name),
                'bank' => strip_tags($request->bank),
                'stk' => strip_tags($request->stk),
                'qr_code' => $qrCodePath ?? $bankAccount->qr_code,
            ]);

            Cache::forget("user_banks_{$user->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật tài khoản thành công!',
            ]);
        }

        UserBankAccount::create([
            'user_id' => $user->id,
            'name' => strip_tags($request->name),
            'bank' => strip_tags($request->bank),
            'stk' => strip_tags($request->stk),
            'qr_code' => $qrCodePath,
        ]);

        Cache::forget("user_banks_{$user->id}");

        return response()->json([
            'status' => 'success',
            'message' => 'Thêm thông tin tài khoản thành công!',
        ]);
    }

    public function deleteBankAccount(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:user_bank_accounts,id',
        ]);

        $user = Auth::user();
        $bankAccount = UserBankAccount::where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$bankAccount) {
            return response()->json(['status' => 'error', 'message' => 'Không có quyền xóa tài khoản này!'], 403);
        }

        $bankAccount->delete();
        Cache::forget("user_banks_{$user->id}");

        return response()->json([
            'status' => 'success',
            'message' => 'Đã xóa tài khoản thành công!',
        ]);
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'bank_id' => 'required|exists:user_bank_accounts,id',
        ]);

        $user = Auth::user();

        // [LOCK] Ngăn chặn spam rút tiền
        $lockKey = "withdrawal_lock_{$user->id}";
        $lock = Cache::lock($lockKey, 10); // Khóa trong 10 giây

        if (!$lock->get()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn đang thực hiện một yêu cầu rút tiền khác. Vui lòng đợi!',
            ], 429);
        }

        try {
            $bankAccount = UserBankAccount::where('id', $request->bank_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$bankAccount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tài khoản ngân hàng không hợp lệ!',
                ], 400);
            }

            $fixedFee = env('WITHDRAWAL_FIX_FEE', 0);

            return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $fixedFee, $bankAccount) {
                // Tìm các đơn chưa quy đổi và khóa row để chống double-click (race condition)
                $unredeemedTransactions = \App\Models\Transaction::where('user_id', $user->id)
                    ->where('is_redeemed', 0)
                    ->lockForUpdate()
                    ->get();

                if ($unredeemedTransactions->isEmpty()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Không có giao dịch nào để rút!',
                    ], 400);
                }

                $amount = $unredeemedTransactions->sum('actual_amount');

                if ($amount <= 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Số dư không hợp lệ!',
                    ], 400);
                }

                // Tạo lệnh rút
                $withdrawal = Withdrawal::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'fee' => $fixedFee,
                    'bank' => $bankAccount->bank,
                    'stk' => $bankAccount->stk,
                    'name' => $bankAccount->name,
                    'qr_code' => $bankAccount->qr_code,
                    'status' => 0,
                ]);

                // Liên kết các đơn và đánh dấu đã quy đổi
                foreach ($unredeemedTransactions as $tx) {
                    $tx->update([
                        'is_redeemed' => 1,
                        'withdrawal_id' => $withdrawal->id,
                    ]);
                }

                // Đồng bộ điểm User (đảm bảo diem = 0 sau khi rút hết các đơn)
                $user->diem = 0;
                $user->save();

                Cache::forget('admin_total_pending');
                Cache::forget("user_withdrawals_{$user->id}");

                // Bắn thông báo sang tele (Async)
                SendTelegramNotificationJob::dispatch('admin_withdraw', [
                    'id' => $withdrawal->id,
                    'user_id' => $user->id,
                    'name' => $withdrawal->name,
                    'stk' => $withdrawal->stk,
                    'bank' => $withdrawal->bank,
                    'amount' => $withdrawal->amount,
                    'fee' => $withdrawal->fee,
                    'qr_code' => $withdrawal->qr_code,
                ]);

                return response()->json([
                    'status' => 'success',
                    'withdrawal_id' => $withdrawal->id,
                    'message' => 'Đã tạo lệnh rút toàn bộ số dư (' . number_format($amount, 0, ',', '.') . 'đ). Phí rút: ' . number_format($fixedFee) . 'đ. Vui lòng chờ duyệt!',
                ]);
            });
        } finally {
            // Giải phóng khóa nếu cần thiết, hoặc để tự hết hạn sau 10s
            // $lock->release();
        }
    }

    public function getWithdrawalDetails(Request $request)
    {
        $user = Auth::user();
        $withdrawal = Withdrawal::with('transactions')
            ->where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

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
        $user = Auth::user();

        $withdrawal = Withdrawal::with('transactions')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $transactions = $withdrawal->transactions;

        $filename = "withdrawal_{$id}_transactions.csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "no-store, no-cache, must-revalidate",
            "Expires" => "0",
            "X-Content-Type-Options" => "nosniff",
        ];

        $columns = [
            'TX ID',
            'STK Number',
            'Amount (VND)',
            'Actual Amount (VND)',
            'Completion Time',
            'Description'
        ];

        $callback = function () use ($transactions, $columns) {
            $file = fopen('php://output', 'w');


            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, $columns);

            foreach ($transactions as $tx) {

                $masked = Str::mask($tx->va_number, '*', 0, 12);

                fputcsv($file, [
                    // 🔥 ép text dài để Excel “auto rộng”
                    '="' . $tx->tx_id . '"',

                    '="' . $masked . '"',

                    // thêm khoảng trắng để nhìn rộng hơn
                    number_format($tx->amount) . ' ',

                    number_format($tx->actual_amount) . ' ',

                    '="' . $tx->completion_time . '"',

                    // giữ nguyên nhưng thêm khoảng trắng tránh bị cụt
                    $tx->description . ' ',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportUnredeemed()
    {
        $user = Auth::user();
        $transactions = \App\Models\Transaction::where('user_id', $user->id)
            ->where('is_redeemed', 0)
            ->get();

        if ($transactions->isEmpty()) {
            return back()->with('error', 'Không có đơn nào chưa quy đổi.');
        }

        $filename = "unredeemed_transactions_" . now()->format('Ymd_His') . ".csv";
        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        ];

        $columns = ['TX ID', 'STK Number', 'Amount (VND)', 'Actual Amount (VND)', 'Completion Time', 'Description'];

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
}
