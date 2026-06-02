<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>

    <!-- Telegram WebApp -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 420px;
            width: 100%;
            animation: fadeIn 0.5s ease;
        }

        h1 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 24px;
        }

        p {
            color: #4a5568;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .status {
            font-size: 16px;
            font-weight: 600;
        }

        .loading {
            color: #3182ce;
        }

        .error {
            color: #e53e3e;
        }

        .success {
            color: #38a169;
        }

        .alert-info {
            background: #ebf8ff;
            color: #2b6cb0;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #bee3f8;
            margin-bottom: 20px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px);}
            to { opacity: 1; transform: translateY(0);}
        }
    </style>
</head>

<body>

<div class="container">
    <h1>🚀 Tài khoản SYSTEM</h1>

    @if(auth()->check())

        @if(auth()->user()->is_blocked)
            <p class="status error">🚫 Tài khoản bị chặn</p>

        @elseif(!auth()->user()->is_approved)
            <div class="alert-info">
                ⏳ Tài khoản đang chờ Admin phê duyệt
            </div>

            <p>Hệ thống sẽ tự động chuyển khi được duyệt...</p>

        @else
            <p class="status success">✅ Đã đăng nhập</p>

            <script>
                window.location.href = '/vacreate';
            </script>
        @endif

    @else

        <p id="loginStatus" class="status loading">
            🔄 Đang đăng nhập bằng Telegram...
        </p>

    @endif
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {

        // Nếu đã login rồi thì không cần chạy nữa
        @if(auth()->check())
            return;
        @endif

        const tg = window.Telegram.WebApp;

        // ❌ Không mở từ Telegram
        if (!tg || !tg.initData) {
            document.getElementById('loginStatus').innerHTML = "❌ Vui lòng mở từ Telegram";
            document.getElementById('loginStatus').classList.add('error');
            return;
        }

        tg.expand();


        fetch('/auth/telegram', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: new URLSearchParams({
                initData: tg.initData
            })
        })
            .then(res => res.json())
            .then(data => {

                if (data.blocked) {
                    document.getElementById('loginStatus').innerHTML = "🚫 Tài khoản bị chặn";
                    document.getElementById('loginStatus').classList.remove('loading');
                    document.getElementById('loginStatus').classList.add('error');
                    return;
                }

                if (data.success) {

                    if (data.approved) {
                        document.getElementById('loginStatus').innerHTML = "✅ Đăng nhập thành công...";
                        setTimeout(() => {
                            window.location.href = '/vacreate';
                        }, 800);
                    } else {
                        document.getElementById('loginStatus').innerHTML = "⏳ Đang chờ duyệt...";
                        document.getElementById('loginStatus').classList.remove('loading');
                    }

                } else {
                    document.getElementById('loginStatus').innerHTML = "❌ Login thất bại";
                    document.getElementById('loginStatus').classList.add('error');
                }

            })
            .catch(() => {
                document.getElementById('loginStatus').innerHTML = "❌ Lỗi kết nối server";
                document.getElementById('loginStatus').classList.add('error');
            });

    });



    @if(auth()->check() && !auth()->user()->is_approved)
    setInterval(() => {
        fetch('/check-approval')
            .then(res => res.json())
            .then(data => {
                if (data.approved) {
                    window.location.href = '/vacreate';
                }
            });
    }, 3000);
    @endif
</script>

</body>
</html>