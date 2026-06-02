<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truy cập bị từ chối - BÁNH ẢO</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            color: white;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .container {
            text-align: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        .glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.4) 0%, transparent 70%);
            filter: blur(40px);
            z-index: -1;
            animation: pulse 4s infinite ease-in-out;
        }
        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.8; }
        }
        h1 { font-size: 8rem; margin: 0; line-height: 1; color: #ef4444; }
        h2 { font-size: 2rem; margin: 1rem 0; }
        p { color: #94a3b8; max-width: 500px; margin: 0 auto 2rem; line-height: 1.6; }
        .btn-home {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.5);
        }
        .ip-info {
            margin-top: 3rem;
            font-size: 0.9rem;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="glow"></div>
    <div class="container">
        <h1>403</h1>
        <h2>TRUY CẬP BỊ CHẶN</h2>
        <p>Hệ thống phát hiện các hành vi bất thường hoặc nghi ngờ spam từ địa chỉ IP này. Vui lòng liên hệ Admin nếu bạn tin rằng đây là một sai sót.</p>
        <a href="#" onclick="alert('Vui lòng liên hệ Admin qua Telegram!')" class="btn-home">LIÊN HỆ HỖ TRỢ</a>
        <div class="ip-info">IP của bạn: {{ request()->ip() }}</div>
    </div>
</body>
</html>
