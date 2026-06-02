<?php
http_response_code(200);
echo 'OK';
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// 2. Lấy nội dung yêu cầu
$rawBody = file_get_contents("php://input");
$time = date('Y-m-d H:i:s');
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// LOG INPUT
file_put_contents('telegram_in.log', "[$time] REQUEST FROM TELEGRAM:\n$rawBody\n\n", FILE_APPEND);

// 3. Chuyển tiếp tới Laravel nội bộ
// Thử cổng 80 (Laragon) hoặc 8000 (Artisan Serve)
$targetUrl = "https://botcamap.fun/telegram/webhook";


$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Host: $host"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

// LOG KẾT QUẢ CHUYỂN TIẾP (Để debug)
file_put_contents('telegram_out.log', "[$time] FORWARDED TO: $targetUrl | HTTP CODE: $httpCode\nLOCAL RESPONSE: $result\nERROR: $err\n\n", FILE_APPEND);
exit;