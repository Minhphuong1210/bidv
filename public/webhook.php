<?php

// 1. Phản hồi OK ngay lập tức cho Telegram để tránh bị lặp (Duplicate)
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
$targetUrl = "https://habitant-washstand-ought.ngrok-free.dev/telegram/webhook";


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



// // ===== DEBUG LOG FILE =====
// function log_debug($data, $file = 'log.txt') {
//     file_put_contents($file, date('Y-m-d H:i:s') . " | " . print_r($data, true) . PHP_EOL, FILE_APPEND);
// }

// // ===== CONFIG =====
// $botToken = "8610845196:AAFnL1mhzuTzKe0CR3ZJri5ojwEFEZ9Kxx8";

// // ===== GET RAW INPUT =====
// $raw = file_get_contents("php://input");
// log_debug($raw, 'raw.txt');

// $update = json_decode($raw, true);

// if (!$update) {
//     log_debug("NO UPDATE");
//     exit;
// }

// if (!isset($update['message'])) {
//     log_debug("NO MESSAGE");
//     exit;
// }

// // ===== PARSE MESSAGE =====
// $message = $update['message'];
// $text = $message['text'] ?? '';
// $user = $message['from'] ?? [];
// $chat_id = $message['chat']['id'] ?? null;

// log_debug([
//     'text' => $text,
//     'user' => $user,
//     'chat_id' => $chat_id
// ]);

// // ===== ONLY HANDLE /start =====
// if (strpos($text, '/start') !== 0) {
//     log_debug("NOT START COMMAND");
//     exit;
// }

// // ===== GET TOKEN =====
// $token = trim(str_replace('/start', '', $text));
// if (!$token) {
//     log_debug("TOKEN EMPTY");
//     exit;
// }

// // ===== USER INFO =====
// $telegram_id = $user['id'] ?? null;
// $username    = $user['username'] ?? null;
// $first_name  = $user['first_name'] ?? null;
// $last_name   = $user['last_name'] ?? null;

// // ===== DB CONNECT =====
// try {
//     $pdo = new PDO(
//         "mysql:host=127.0.0.1;dbname=web_create_va;charset=utf8mb4",
//         "root",
//         "",
//         [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
//     );
// } catch (Exception $e) {
//     log_debug("DB CONNECT ERROR: " . $e->getMessage(), 'error.txt');
//     exit;
// }

// try {

//     $pdo->beginTransaction();

//     // =========================
//     // 1. INSERT / UPDATE USERS
//     // =========================
//     $stmt = $pdo->prepare("
//         INSERT INTO users (
//             telegram_id,
//             telegram_username,
//             telegram_first_name,
//             telegram_last_name,
//             chat_id,
//             telegram_last_login,
//             login_count,
//             created_at,
//             updated_at
//         )
//         VALUES (?, ?, ?, ?, ?,NOW(), 1, NOW(), NOW())
//         ON DUPLICATE KEY UPDATE
//             id = LAST_INSERT_ID(id),
//             telegram_username = VALUES(telegram_username),
//             telegram_first_name = VALUES(telegram_first_name),
//             telegram_last_name = VALUES(telegram_last_name),
//             chat_id = VALUES(chat_id),
//             telegram_last_login = NOW(),
//             login_count = login_count + 1,
//             updated_at = NOW()
//     ");

//     $stmt->execute([
//         $telegram_id,
//         $username,
//         $first_name,
//         $last_name,
//         $chat_id
//     ]);

//     // =========================
//     // 2. GET USER ID
//     // =========================
//     $userId = $pdo->lastInsertId();

//     if (!$userId) {
//         $stmt = $pdo->prepare("SELECT id FROM users WHERE telegram_id = ? LIMIT 1");
//         $stmt->execute([$telegram_id]);
//         $userId = $stmt->fetchColumn();
//     }

//     log_debug("USER ID: " . $userId);

//     if (!$userId) {
//         throw new Exception("USER ID NULL");
//     }

//     // =========================
//     // 3. UPDATE LOGIN TOKENS
//     // =========================
//     $stmt = $pdo->prepare("
//         UPDATE login_tokens
//         SET used = 1,
//             telegram_id = ?,
//             user_id = ?
//         WHERE token = ?
//           AND used = 0
//           AND expired_at > NOW()
//     ");

//     $stmt->execute([
//         $telegram_id,
//         $userId,
//         $token
//     ]);

//     $affected = $stmt->rowCount();
//     log_debug("TOKEN UPDATED ROWS: " . $affected);

//     if ($affected == 0) {
//         throw new Exception("TOKEN INVALID OR EXPIRED");
//     }

//     $pdo->commit();

// } catch (Exception $e) {
//     $pdo->rollBack();
//     log_debug("ERROR: " . $e->getMessage(), 'error.txt');

//     // báo lỗi về telegram
//     if ($chat_id) {
//         file_get_contents(
//             "https://api.telegram.org/bot$botToken/sendMessage?" .
//             http_build_query([
//                 'chat_id' => $chat_id,
//                 'text' => "❌ Login thất bại: " . $e->getMessage()
//             ])
//         );
//     }

//     exit;
// }

// $first_name_safe = htmlspecialchars($first_name ?? '');
// $last_name_safe  = htmlspecialchars($last_name ?? '');
// $username_safe   = htmlspecialchars($username ?? '');

// $textSend = "👤 <b>USER INFO</b>\n\n"
//     . "🆔 ID: <code>{$telegram_id}</code>\n"
//     . "👤 Username: <code>{$username_safe}</code>\n"
//     . "📛 First name: <b>{$first_name_safe}</b>\n"
//     . "📛 Last name: <b>{$last_name_safe}</b>\n";

// file_get_contents(
//     "https://api.telegram.org/bot$botToken/sendMessage?" .
//     http_build_query([
//         'chat_id' => $chat_id,
//         'text' => $textSend,
//         'parse_mode' => 'HTML'
//     ])
// );

// log_debug("DONE SUCCESS");

// exit;
