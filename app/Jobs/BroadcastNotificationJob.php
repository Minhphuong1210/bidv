<?php

namespace App\Jobs;

use App\Http\Services\TelegramNotifier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BroadcastNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;



    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
        $this->onQueue('broadcast');
    }

    public function handle(TelegramNotifier $notifier)
    {
        Log::info("[BroadcastJob] Started sending broadcast.");

        // Lấy tất cả user có telegram_id và không bị khóa
        User::whereNotNull('telegram_id')
            ->where('is_blocked', 0)
            ->chunk(50, function ($users) use ($notifier) {
                foreach ($users as $user) {
                    try {
                        $notifier->sendMessage($user->telegram_id, $this->message);
                        // Tránh bị Telegram rate limit (nhẹ)
                        usleep(50000); // 50ms
                    } catch (\Exception $e) {
                        Log::error("[BroadcastJob] Failed to send to {$user->telegram_id}: " . $e->getMessage());
                    }
                }
            });

        Log::info("[BroadcastJob] Finished.");
    }
}
