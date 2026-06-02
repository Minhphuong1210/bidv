<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class TelegramServiceSenDocument
{
    protected $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.token');
    }

    public function sendDocument($chatId, $filePath, $fileName = 'file.xlsx', $caption = 'File Excel của bạn')
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendDocument";

        $http = Http::timeout(30);

        // Rotating Proxy Logic
        $proxiesStr = \Illuminate\Support\Facades\Cache::remember('telegram_proxies', 60, fn() => \App\Models\Setting::where('key', 'TELEGRAM_PROXIES')->value('value') ?: env('TELEGRAM_PROXIES'));
        if ($proxiesStr) {
            $proxies = preg_split('/[\n\r,]+/', $proxiesStr);
            $proxies = array_filter(array_map('trim', $proxies));

            if (count($proxies) > 0) {
                $selectedProxy = $proxies[array_rand($proxies)];
                $http = $http->withOptions([
                    'proxy' => 'http://' . $selectedProxy
                ]);
            }
        }

        return $http->attach(
            'document',
            file_get_contents($filePath),
            $fileName
        )->post($url, [
            'chat_id' => $chatId,
            'caption' => $caption
        ]);
    }
}