<?php
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\VaController;
use App\Http\Controllers\TelegramWebAppController;

Route::get('/', function () {
    if (!auth()->check()) {
        return view('test'); // Will contain Telegram login
    }
    if (!auth()->user()->is_approved && auth()->user()->role !== 'admin') {
        return view('test'); // Will show "waiting for approval" message
    }
    return redirect('/vacreate');
});




Route::post('/telegram/webhook', [\App\Http\Controllers\TelegramWebhookController::class, 'handle']);

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login/telegram', [AuthController::class, 'redirectToTelegram']);
Route::get('/telegram/handle', [AuthController::class, 'handleLogin']);
Route::post('/check-login', [AuthController::class, 'checkLogin']);
Route::get('/logout', [AuthController::class, 'logout']);
Route::post('/auth/telegram', [TelegramWebAppController::class, 'login']);
Route::middleware(['auth.tele'])->group(function () {
    Route::get('/vacreate', [VaController::class, 'index'])->name('user.vacreate');
    Route::get('/profile', [VaController::class, 'index'])->name('user.profile');
    Route::get('/withdraw', [VaController::class, 'index'])->name('user.withdraw');

    Route::get(
        '/dashboard',
        function () {
            return redirect('/vacreate');
        }
    );
});



Route::middleware('auth')->group(function () {
    Route::get('/va', [VaController::class, 'index']);
    Route::get('/va/history', [VaController::class, 'history']);
    Route::get('/user/stats', [UserController::class, 'getStats']);
    Route::get(
        '/check-approval',
        function () {
            return response()->json(['approved' => auth()->user()->is_approved]);
        }
    );

    // Throttled routes to prevent spam (10 requests / minute)
    Route::middleware('throttle:6,1')->group(
        function () {
            Route::post('/va/create-va-multiple', [VaController::class, 'createVaMultiple']);
            Route::post('/va/create', [VaController::class, 'create']);
            Route::post('/user/profile', [UserController::class, 'updateProfile']);
            Route::post('/user/withdraw', [UserController::class, 'withdraw']);
            Route::post('/user/bank/delete', [UserController::class, 'deleteBankAccount']);
            Route::get('/user/withdrawal/{id}/details', [UserController::class, 'getWithdrawalDetails']);
            Route::get('/user/withdrawal/{id}/export', [UserController::class, 'exportWithdrawal']);
            Route::get('/user/transactions/export-unredeemed', [UserController::class, 'exportUnredeemed']);
        }
    );
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/admin/mass-create', [AdminController::class, 'massCreateVa']);
    Route::post('/admin/users/toggle-approval', [AdminController::class, 'toggleApproval']);
    Route::post('/admin/users/toggle-block', [AdminController::class, 'toggleBlock']);
    Route::post('/admin/users/delete', [AdminController::class, 'deleteUser']);
    Route::post('/admin/withdrawals/approve', [AdminController::class, 'approveWithdrawal']);
    Route::post('/admin/withdrawals/reject', [AdminController::class, 'rejectWithdrawal']);
    Route::get('/admin/withdrawal/{id}/details', [AdminController::class, 'getWithdrawalDetails']);
    Route::get('/admin/withdrawal/{id}/export', [AdminController::class, 'exportWithdrawal']);
    Route::get('go-bo-ip', [AdminController::class, 'goBoIp']);
    Route::post('/admin/removeIp/approve', [AdminController::class, 'removeIp']);
    Route::post('/admin/tao-theo-yeu-cau-create', [AdminController::class, 'taoTheoYeuCauCreate']);
    Route::get('/admin/tao-theo-yeu-cau-export', [AdminController::class, 'exportTaoTheoYeuCau']);
    Route::get('/admin/withdrawals/latest', [AdminController::class, 'getLatestWithdrawals']);
    Route::post('/admin/broadcast', [AdminController::class, 'sendBroadcast']);
    Route::post('/admin/proxy-settings/save', [AdminController::class, 'saveProxySettings']);
    Route::get('/admin/{tab?}', [AdminController::class, 'dashboard'])->name('admin.dashboard');

});
