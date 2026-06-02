<?php

namespace App\Jobs;

use App\Http\Services\TelegramNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $type;
    protected $data;
    protected $chatId;

    /**
     * Create a new job instance.
     * 
     * @param string $type ('user_credit', 'admin_credit', 'admin_withdraw', 'generic')
     * @param array $data
     * @param string|null $chatId
     */
    public function __construct(string $type, array $data, ?string $chatId = null)
    {
        $this->type = $type;
        $this->data = $data;
        $this->chatId = $chatId;
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramNotifier $notifier): void
    {
        switch ($this->type) {
            case 'user_credit':
                $notifier->sendToUser(
                    $this->chatId,
                    $this->data['amount'],
                    $this->data['va_number'],
                    $this->data['tx_id'],
                    $this->data['description'],
                    $this->data['actual_amount'],
                    $this->data['merchant_name'] ?? ''
                );
                break;

            case 'admin_credit':
                $notifier->sendToAdmin($this->data);
                break;

            case 'admin_withdraw':
                // We recreate the withdrawal object structure for the notifier
                $withdrawal = (object) $this->data;
                $notifier->sendToAdminWithdraw($withdrawal);
                $notifier->autoSendWithdrawalExport($withdrawal);
                break;

            case 'generic':
                $notifier->sendMessage($this->chatId, $this->data['message']);
                break;
        }
    }
}
