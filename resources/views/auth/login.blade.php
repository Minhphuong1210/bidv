<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Login Telegram</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            height: 100vh;
            background: linear-gradient(135deg, #667eea, #764ba2, #ff6a00);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* nền animation */
        body::before {
            content: "";
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 10%, transparent 10%);
            background-size: 50px 50px;
            animation: moveBg 10s linear infinite;
            pointer-events: none;
            z-index: -1;
        }

        @keyframes moveBg {
            from {
                transform: translate(0, 0);
            }

            to {
                transform: translate(-50px, -50px);
            }
        }

        /* box login */
        .login-box {
            position: relative;
            width: 350px;
            padding: 40px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            text-align: center;
            color: white;
        }

        .login-box h2 {
            margin-bottom: 10px;
        }

        .login-box p {
            font-size: 14px;
            margin-bottom: 25px;
        }

        /* nút login */
        .btn-tele {
            display: inline-block;
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background: linear-gradient(45deg, #0088cc, #00c6ff);
            color: #fff;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-tele:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        /* loading */
        .loading {
            display: none;
            margin-top: 15px;
            font-size: 14px;
        }

        /* logo */
        .logo {
            font-size: 50px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <div class="login-box">
        <div class="logo">💎</div>
        <h2>Đăng nhập Telegram</h2>
        <p>Đăng nhập nhanh chóng & bảo mật</p>

        <button class="btn-tele" onclick="loginTele()">
            🚀 Đăng nhập bằng Telegram
        </button>

        <div class="loading" id="loading">
            🔄 Đang chuyển đến Telegram...
        </div>
    </div>

    <script src="{{ asset('js/login.js') }}"></script>

</body>

</html>