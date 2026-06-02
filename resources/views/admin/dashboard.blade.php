<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - HPay</title>
    @include('layout._style_css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
            color: #333;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Layout */
        .sidebar {
            width: 260px;
            background: #1e1e2d;
            color: #fff;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 22px 20px;
            font-size: 22px;
            font-weight: 800;
            text-align: center;
            border-bottom: 1px solid #2b2b3f;
            background: #1a1a27;
            color: #fff;
            letter-spacing: 1px;
        }

        .sidebar-header span {
            color: #e91e63;
        }

        .sidebar-menu {
            padding: 15px 0;
            flex: 1;
            overflow-y: auto;
        }

        .menu-item {
            padding: 16px 25px;
            display: block;
            color: #a2a3b7;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 15px;
        }

        .menu-item:hover,
        .menu-item.active {
            background: #1b1b29;
            color: #fff;
            border-left: 4px solid #e91e63;
        }

        .menu-item i {
            width: 25px;
            text-align: center;
            margin-right: 12px;
            font-size: 18px;
        }

        /* Main Content wrapper */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .navbar {
            background: #fff;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            z-index: 10;
        }

        .navbar .page-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .btn-home {
            background: #f0f0f0;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            color: #555;
            font-weight: bold;
            font-size: 14px;
            transition: 0.2s;
        }

        .btn-home:hover {
            background: #e0e0e0;
        }

        .content-container {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Stats Cards */
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            color: #fff;
        }

        .stat-info h4 {
            margin: 0 0 8px 0;
            color: #888;
            font-size: 13px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .stat-info h2 {
            margin: 0;
            color: #333;
            font-size: 26px;
            font-weight: 800;
        }

        .bg-users {
            background: linear-gradient(135deg, #1e88e5, #1565c0);
            box-shadow: 0 5px 15px rgba(30, 136, 229, 0.3);
        }

        .bg-in {
            background: linear-gradient(135deg, #4caf50, #2e7d32);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .bg-withdraw {
            background: linear-gradient(135deg, #e91e63, #c2185b);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.3);
        }

        .bg-pending {
            background: linear-gradient(135deg, #ff9800, #ef6c00);
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
        }

        /* Sections */
        .section-content {
            display: none;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
        }

        .section-content.active {
            display: block;
            animation: fadeIn 0.4s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 700;
            color: #444;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        tr:hover {
            background-color: #fcfcfc;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }

        .badge.success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge.pending {
            background: #fff8e1;
            color: #f57f17;
        }

        .badge.danger {
            background: #ffebee;
            color: #c62828;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin-right: 5px;
            color: #fff;
            transition: opacity 0.2s;
        }

        .action-btn:hover {
            opacity: 0.9;
        }

        .btn-approve {
            background: #4caf50;
        }

        .btn-reject {
            background: #f44336;
        }

        .btn-toggle {
            background: #2196f3;
        }

        .btn-delete {
            background: #9e9e9e;
        }

        /* Mass Create Forms */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            outline: none;
            transition: border-color 0.3s;
            background: #fafafa;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #e91e63;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.1);
        }

        .bank-list {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .bank {
            padding: 12px 24px;
            border: 2px solid #eee;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            color: #666;
            transition: all 0.2s;
            background: #fff;
        }

        .bank:hover {
            border-color: #ccc;
        }

        .bank.active {
            background: #e91e63;
            color: #fff;
            border-color: #e91e63;
            box-shadow: 0 4px 10px rgba(233, 30, 99, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #e91e63, #f06292);
            border: none;
            padding: 14px 30px;
            color: #fff;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: transform 0.2s;
            box-shadow: 0 4px 15px rgba(233, 30, 99, 0.3);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .result-box {
            display: none;
            margin-top: 25px;
            padding: 25px;
            border-radius: 12px;
            background: #fff;
            border-left: 5px solid #e91e63;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .item-list {
            max-height: 300px;
            overflow-y: auto;
            background: #fafafa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
            margin-top: 10px;
        }

        .btn-export {
            background: #2e7d32;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            float: right;
            margin-top: -35px;
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
        }

        /* Modern Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #999;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-bolt"></i> ADMIN <span>HUB</span>
        </div>
        <div class="sidebar-menu">
            <a href="/admin/dashboard" class="menu-item @if ($tab == 'dashboard') active @endif">
                <i class="fa-solid fa-chart-pie"></i> Thống Kê
            </a>
            <a href="/admin/users" class="menu-item @if ($tab == 'users') active @endif">
                <i class="fa-solid fa-users"></i> Quản Lý Users
            </a>
            <a href="/admin/withdrawals" class="menu-item @if ($tab == 'withdrawals') active @endif">
                <i class="fa-solid fa-money-bill-transfer"></i> Lệnh Rút Tiền
            </a>
            <a href="/admin/mass-create" class="menu-item @if ($tab == 'mass-create') active @endif">
                <i class="fa-solid fa-boxes-stacked"></i> Tạo Hàng Loạt
            </a>

            <a href="/admin/tao-theo-danh-sach" class="menu-item @if ($tab == 'tao-theo-danh-sach') active @endif">
                <i class="fa-solid fa-boxes-stacked"></i> Tạo tên theo danh sách
            </a>

            <a href="/admin/go-bo-ip" class="menu-item @if ($tab == 'go-bo-ip') active @endif">
                <i class="fa-solid fa-boxes-stacked"></i> Gỡ chặn user
            </a>

            <a href="/admin/broadcast" class="menu-item @if ($tab == 'broadcast') active @endif">
                <i class="fa-solid fa-paper-plane"></i> Gửi thông báo
            </a>

            <a href="/admin/proxy-settings" class="menu-item @if ($tab == 'proxy-settings') active @endif">
                <i class="fa-solid fa-network-wired"></i> Cấu hình Proxy
            </a>

        </div>
    </div>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="navbar">
            <div class="page-title" id="pageTitleText">Tổng Quan Thống Kê</div>
            <div>
                <a href="/" class="btn-home"><i class="fa-solid fa-house"></i> Trang Chủ Web</a>
            </div>
        </div>

        <div class="content-container">

            <!-- SECTION: DASHBOARD -->
            @if ($tab == 'dashboard')
                <form method="GET" style="margin-bottom:20px; display:flex; gap:10px; align-items:center;">

                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        style="padding:8px 12px;border-radius:8px;border:1px solid #ddd;">

                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        style="padding:8px 12px;border-radius:8px;border:1px solid #ddd;">

                    <button type="submit"
                        style="padding:8px 16px;border:none;background:linear-gradient(135deg,#4facfe,#00f2fe);color:#fff;border-radius:8px;">
                        Lọc
                    </button>

                    <a href="/admin/dashboard"
                        style="padding:8px 16px;background:#eee;border-radius:8px;text-decoration:none;">
                        Reset
                    </a>

                </form>

                <div class="section-content active" id="sec-dashboard">
                    <h3 style="margin-top:0; color:#333;">Biểu đồ Hoạt Động</h3>
                    <p style="color:#777; margin-bottom: 25px;">Tóm tắt nhanh số liệu của toàn bộ hệ thống.</p>

                    <div class="stat-cards">
                        {{-- <div class="stat-card">
                            <div class="stat-icon bg-users"><i class="fa-solid fa-user-group"></i></div>
                            <div class="stat-info">
                                <h4>Tổng Người Dùng</h4>
                                <h2>{{ number_format($totalUsers ?? 0) }}</h2>
                            </div>
                        </div> --}}
                        {{-- <div class="stat-card">
                            <div class="stat-icon bg-pending"><i class="fa-solid fa-user-clock"></i></div>
                            <div class="stat-info">
                                <h4>Chờ Duyệt Mới</h4>
                                <h2 style="color: #ef6c00;">{{ number_format($totalPendingUsers ?? 0) }}</h2>
                            </div>
                        </div> --}}
                        <div class="stat-card">
                            <div class="stat-icon bg-in"><i class="fa-solid fa-arrow-turn-down"></i></div>
                            <div class="stat-info">
                                <h4>Tổng Tiền Vào</h4>
                                <h2>{{ number_format($totalIn ?? 0) }} đ</h2>
                            </div>
                        </div>


                        <div class="stat-card">
                            <div class="stat-icon bg-pending"><i class="fa-solid fa-user-clock"></i></div>
                            <div class="stat-info">
                                <h4>Tống số đơn trên 330000 </h4>
                                <h2 style="color: #ef6c00;">{{ number_format($countAbove330 ?? 0) }}</h2>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon bg-pending"><i class="fa-solid fa-user-clock"></i></div>
                            <div class="stat-info">
                                <h4>Tống số đơn dưới 330000 </h4>
                                <h2 style="color: #ef6c00;">{{ number_format($countBelow330 ?? 0) }}</h2>
                            </div>
                        </div>

                    </div>

                    <div class="stat-cards">
                        <div class="stat-card">
                            <div class="stat-icon bg-withdraw"><i class="fa-solid fa-arrow-right-from-bracket"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Tổng Tiền Đã Rút</h4>
                                <h2>{{ number_format($totalWithdrawn ?? 0) }} đ</h2>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon bg-pending"><i class="fa-regular fa-clock"></i></div>
                            <div class="stat-info">
                                <h4>Tổng tiền của người dùng</h4>
                                <h2>{{ number_format($totalPending ?? 0) }} đ</h2>
                            </div>
                        </div>

                       
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-pending"><i class="fa-regular fa-clock"></i></div>
                        <div class="stat-info">
                            <h4>Tiền lãi</h4>

                            @php

                                $total_con_lai = $totalIn - $totalOut;

                            @endphp

                            <h2>{{ number_format($total_con_lai ?? 0) }} đ</h2>
                        </div>
                    </div>
                </div>
            @endif

            <!-- SECTION: USERS -->
            @if ($tab == 'users')
                <div class="section-content active" id="sec-users">
                    <h3 style="margin-top:0;">Quản Lý Người Dùng</h3>
                    <p style="color: #666; margin-bottom: 20px;">Danh sách tài khoản, chặn/bỏ chặn hoặc xóa người dùng.
                    </p>

                    <form method="GET" action="/admin/users"
                        style="margin-bottom: 20px; display: flex; gap: 10px; max-width: 500px;">
                        <input type="text" name="telegram_id" value="{{ request('telegram_id') }}"
                            placeholder="Nhập Telegram ID / Username..."
                            style="flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; outline: none;">
                        <button type="submit"
                            style="background: #2196f3; color: white; border: none; padding: 0 20px; border-radius: 6px; cursor: pointer; font-weight: bold;"><i
                                class="fa-solid fa-search"></i> Tìm Kiếm</button>
                    </form>

                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên</th>
                                    <th>Telegram ID</th>
                                    <th>Số Dư (Điểm)</th>
                                    <th>Duyệt</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $u)
                                    <tr id="user-row-{{ $u->id }}">
                                        <td>{{ $u->id }}</td>
                                        <td>{{ $u->name ?? $u->telegram_first_name }}</td>
                                        <td>
                                            @if ($u->telegram_username)
                                                <a href="https://t.me/{{ $u->telegram_username }}" target="_blank"
                                                    style="text-decoration:none; color:#2196f3; font-weight:bold;"><i
                                                        class="fa-brands fa-telegram"></i> {{ $u->telegram_id }}</a>
                                            @else
                                                {{ $u->telegram_id }}
                                            @endif
                                        </td>
                                        <td><strong style="color: #4caf50;">{{ number_format($u->diem) }}</strong>
                                        </td>
                                        <td>
                                            @if ($u->is_approved)
                                                <span class="badge success"
                                                    id="approve-status-{{ $u->id }}">Đã
                                                    duyệt</span>
                                            @else
                                                <span class="badge pending"
                                                    id="approve-status-{{ $u->id }}">Chờ
                                                    duyệt</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($u->is_blocked)
                                                <span class="badge danger" id="block-status-{{ $u->id }}">Đã
                                                    bị
                                                    khóa</span>
                                            @else
                                                <span class="badge success"
                                                    id="block-status-{{ $u->id }}">Đang
                                                    hoạt động</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="action-btn btn-approve"
                                                onclick="toggleApproval({{ $u->id }})">
                                                <i class="fa-solid fa-check"></i> <span
                                                    id="btn-approve-text-{{ $u->id }}">
                                                    @if ($u->is_approved)
                                                        Bỏ Duyệt
                                                    @else
                                                        Duyệt
                                                    @endif
                                                </span>
                                            </button>
                                            <button class="action-btn btn-toggle"
                                                onclick="toggleBlock({{ $u->id }})">
                                                <i class="fa-solid fa-shield-halved"></i> <span
                                                    id="btn-block-text-{{ $u->id }}">
                                                    @if ($u->is_blocked)
                                                        Mở Khóa
                                                    @else
                                                        Khóa
                                                    @endif
                                                </span>
                                            </button>
                                            <button class="action-btn btn-delete"
                                                onclick="deleteUser({{ $u->id }})"><i
                                                    class="fa-solid fa-trash"></i> Xóa</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- SECTION: WITHDRAWALS -->
            @if ($tab == 'withdrawals')
                <div class="section-content active" id="sec-withdrawals">
                    <h3 style="margin-top:0;">Lệnh Rút Tiền</h3>
                    <p style="color: #666; margin-bottom: 20px;">Xử lý các yêu cầu rút tiền từ người dùng.</p>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Số Tiền</th>
                                    <th>Ngân Hàng</th>
                                    <th>Tài Khoản</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($withdrawals as $w)
                                    <tr id="with-row-{{ $w->id }}">
                                        <td>{{ $w->id }}</td>
                                        <td>
                                            @if ($w->user)
                                                <div style="font-weight: 700; color: #333;">
                                                    {{ $w->user->name ?: $w->user->telegram_first_name }}</div>
                                                <a href="https://t.me/{{ $w->user->telegram_username ?: $w->user->telegram_id }}"
                                                    target="_blank"
                                                    style="text-decoration:none; color:#2196f3; font-size: 13px; font-weight:bold;">
                                                    <i class="fa-brands fa-telegram"></i> #{{ $w->user_id }}
                                                </a>
                                            @else
                                                <span style="color: #999;">#{{ $w->user_id }}</span>
                                            @endif
                                        </td>
                                        <td><strong style="color: #e91e63;">{{ number_format($w->amount) }} đ</strong>
                                        </td>
                                        <td>{{ $w->bank }}</td>
                                        <td style="font-size: 14px;">
                                            <div style="font-weight: 700; color: #e91e63;">{{ $w->stk }}</div>
                                            <div style="color: #666; font-size: 13px; margin-top: 3px;">
                                                {{ $w->name }}</div>
                                            @if ($w->qr_code)
                                                <div style="margin-top: 8px;">
                                                    <a href="{{ asset($w->qr_code) }}" target="">
                                                        <img src="{{ asset($w->qr_code) }}"
                                                            style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;"
                                                            title="Click để phóng to">
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($w->status == 0 || $w->status === 'pending')
                                                <span class="badge pending" id="status-with-{{ $w->id }}">Chờ
                                                    Duyệt</span>
                                            @elseif($w->status == 1 || $w->status === 'completed')
                                                <span class="badge success">Hoàn Thành</span>
                                            @else
                                                <span class="badge danger">Từ Chối</span>
                                            @endif
                                        </td>
                                        <td id="action-with-{{ $w->id }}">
                                            @if ($w->status == 0 || $w->status === 'pending')
                                                <div id="action-with-{{ $w->id }}"
                                                    style="display: flex; flex-direction: column; gap: 8px;">
                                                    <button class="action-btn btn-approve"
                                                        onclick="actionWithdrawal({{ $w->id }}, 'approve')"><i
                                                            class="fa-solid fa-check"></i> Duyệt</button>
                                                    <button class="action-btn btn-delete"
                                                        onclick="actionWithdrawal({{ $w->id }}, 'reject')"><i
                                                            class="fa-solid fa-xmark"></i> Từ chối</button>
                                                    <button class="action-btn btn-toggle"
                                                        onclick="viewWithdrawalDetails({{ $w->id }})"
                                                        style="background: #2196f3;"><i
                                                            class="fa-solid fa-circle-info"></i> Chi tiết</button>
                                                </div>
                                            @else
                                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                                    <span style="color: #999; font-size: 13px;">Đã xử lý</span>
                                                    <button class="action-btn btn-toggle"
                                                        onclick="viewWithdrawalDetails({{ $w->id }})"
                                                        style="background: #2196f3;"><i
                                                            class="fa-solid fa-circle-info"></i> Chi tiết</button>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- SECTION: MASS CREATE -->
            @if ($tab == 'mass-create')
                <div class="section-content active" id="sec-mass-create">
                    <h3 style="margin-top:0;">Tạo Hàng Loạt Tài Khoản</h3>
                    <p style="color: #666; margin-bottom: 20px;">Hệ thống tự động sinh đuôi tên ngẫu nhiên. Vui lòng
                        nhập thông tin phía dưới.</p>

                    <div
                        style="max-width: 800px; background: #fafafa; padding: 25px; border-radius: 12px; border: 1px solid #eee;">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-weight: 700; margin-bottom: 8px; display: block;">Nhập Người Dùng (User
                                ID):</label>



                            <input type="text" id="mass_user_id" placeholder="VD: NGUYEN VAN">

                        </div>

                        <div class="form-group">
                            <label style="font-weight: 700; margin-bottom: 8px; display: block;">Chọn Ngân
                                Hàng:</label>
                            <div class="bank-list">
                                <div class="bank" data-bank="MSB">MSB</div>
                                <div class="bank" data-bank="KLB">KLB</div>
                                <div class="bank" data-bank="BIDV">BIDV</div>
                            </div>
                            <input type="hidden" id="admin_bank" value="">
                        </div>

                        <div class="form-group">
                            <label>Tiền tố Tên (Prefix):</label>
                            <input type="text" id="admin_prefix" placeholder="VD: NGUYEN VAN">
                        </div>

                        <div class="form-group" style="display: flex; gap: 20px;">
                            <div style="flex: 1;">
                                <label>Độ dài ký tự ngẫu nhiên sau Prefix:</label>
                                <select id="admin_name_length">
                                    <option value="2">2 Ký tự</option>
                                    <option value="3">3 Ký tự</option>
                                    <option value="4" selected>4 Ký tự</option>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label>Số Lượng (Tối đa 100):</label>
                                <input type="number" id="admin_quan" placeholder="VD: 10" max="100">
                            </div>
                            <div style="flex: 1;">
                                <label>Độ dài số tài khoản:</label>
                                <input type="number" id="admin_account_length" value="10" min="6" max="20">
                            </div>
                        </div>

                        <button class="btn-primary" id="massCreateBtn"><i class="fa-solid fa-rocket"></i> BẮT ĐẦU TẠO
                            HÀNG LOẠT</button>
                    </div>

                    <div class="result-box" id="resultBox">
                        <h4 style="margin-top:0; color: #2e7d32;">📊 Kết quả tạo VA</h4>
                        <button class="btn-export" id="exportCsvBtn" style="display:none;"><i
                                class="fa-solid fa-file-excel"></i> Xuất Excel</button>
                        <p>Thành công: <b id="countSuccess" style="color: green">0</b> | Thất bại: <b id="countFail"
                                style="color: red">0</b></p>

                        <b style="color: #555;">Danh sách VA thành công:</b>
                        <div class="item-list" id="listSuccess"></div>

                        <br>
                        <b style="color: #c62828;">Danh sách Thất bại (nếu có):</b>
                        <div class="item-list" id="listFail"
                            style="color:red; border-color: #ffebee; background: #fffcfc;"></div>
                    </div>
                </div>
            @endif

            @if ($tab == 'tao-theo-danh-sach')
                <div class="section-content active" id="sec-mass-create">
                    <h3 style="margin-top:0;">Tạo theo danh sách </h3>
                    <p style="color: #666; margin-bottom: 20px;">Hệ thống tự động sinh đuôi tên ngẫu nhiên. Vui lòng
                        nhập thông tin phía dưới.</p>

                    <div
                        style="max-width: 800px; background: #fafafa; padding: 25px; border-radius: 12px; border: 1px solid #eee;">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-weight: 700; margin-bottom: 8px; display: block;">Nhập Người Dùng (User
                                ID):</label>
                            <input type="text" id="tao_theo_yeu_cau_user_id" placeholder="VD: nhập id user">

                        </div>

                        <div class="form-group">
                            <label style="font-weight: 700; margin-bottom: 8px; display: block;">Chọn Ngân
                                Hàng:</label>
                            <div class="bank-list">
                                <div class="bank" data-bank="MSB">MSB</div>
                                <div class="bank" data-bank="KLB">KLB</div>
                                <div class="bank" data-bank="BIDV">BIDV</div>
                            </div>
                            <input type="hidden" id="admin_bank" value="">
                        </div>

                        <div class="form-group">
                            <label style="font-weight: 700; margin-bottom: 8px; display: block;">Độ dài số tài khoản:</label>
                            <input type="number" id="tao_account_length" value="10" min="6" max="20">
                        </div>

                        <input type="file" id="tao_file">

                        <button class="btn-primary" id="tao_theo_yeu_cau"><i class="fa-solid fa-rocket"></i> BẮT ĐẦU
                            TẠO
                            HÀNG LOẠT</button>
                    </div>
                    <button class="btn-export" id="btnExportTaoYeuCau" style="display:none;">
                        <i class="fa-solid fa-file-excel"></i> Xuất Excel
                    </button>
                    <div class="result-box" id="resultBox">
                        <h4 style="margin-top:0; color: #2e7d32;">📊 Kết quả tạo VA</h4>
                        <button class="btn-export" id="exportCsvBtn" style="display:none;"><i
                                class="fa-solid fa-file-excel"></i> Xuất Excel</button>
                        <p>Thành công: <b id="countSuccess" style="color: green">0</b> | Thất bại: <b id="countFail"
                                style="color: red">0</b></p>

                        <b style="color: #555;">Danh sách VA thành công:</b>
                        <div class="item-list" id="listSuccess"></div>

                        <br>
                        <b style="color: #c62828;">Danh sách Thất bại (nếu có):</b>
                        <div class="item-list" id="listFail"
                            style="color:red; border-color: #ffebee; background: #fffcfc;"></div>
                    </div>
                </div>
            @endif

            @if ($tab == 'go-bo-ip')
                <div class="section-content active" id="go-bo-ip">
                    <h3 style="margin-top:0;">Danh sách các ip đã chặn</h3>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Địa chỉ ip</th>
                                    <th>Tên</th>
                                    <th>Lý do</th>
                                    <th>Thời gian block</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($block_ip as $block_i)
                                    <tr id="with-row-{{ $block_i->id }}">
                                        <td>{{ $block_i->id }}</td>
                                        <td>{{ $block_i->ip_address }}</td>
                                        <td>
                                            @if ($block_i->user)
                                                @php

                                                    $full_name =
                                                        $block_i->user->telegram_first_name .
                                                        ' ' .
                                                        $block_i->user->telegram_last_name;
                                                @endphp

                                                <div style="font-weight: 700; color: #333;">
                                                    {{ $block_i->user->name ?: $block_i->user->telegram_first_name }}
                                                </div>
                                                <a href="https://t.me/{{ $block_i->user->telegram_username ?: $w->user->telegram_id }}"
                                                    target="_blank"
                                                    style="text-decoration:none; color:#2196f3; font-size: 13px; font-weight:bold;">
                                                    <i class="fa-brands fa-telegram"></i> #0088cc {{ $full_name }}



                                                </a>
                                            @else
                                                <span style="color: #999;">#{{ $block_i->user_id }}</span>
                                            @endif
                                        </td>

                                        <td>{{ $block_i->reason }}</td>
                                        <td>{{ $block_i->blocked_at }}</td>

                                        <td id="action-with-{{ $block_i->id }}">

                                            <div id="action-with-{{ $block_i->id }}" style="">
                                                <button class="action-btn btn-approve"
                                                    onclick="actionRemoveIp({{ $block_i->id }}, 'approve')"><i
                                                        class="fa-solid fa-check"></i> Bỏ chặn</button>

                                            </div>

                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <!-- MODAL CHI TIẾT LỆNH RÚT -->



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function toggleApproval(id) {
            if (!confirm('Thay đổi trạng thái DUYỆT cho user này?')) return;
            $.post('/admin/users/toggle-approval', {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: id
            }, function(res) {
                if (res.status == 'success') {
                    alert('Cập nhật trạng thái duyệt thành công!');
                    location.reload();
                } else alert(res.message);
            });
        }

        function toggleBlock(id) {
            if (!confirm('Thay đổi trạng thái KHÓA cho user này?')) return;
            $.post('/admin/users/toggle-block', {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: id
            }, function(res) {
                if (res.status == 'success') {
                    alert('Cập nhật trạng thái khóa thành công!');
                    location.reload();
                } else alert(res.message);
            });
        }

        function deleteUser(id) {
            if (!confirm('Xác nhận XÓA user này vĩnh viễn?')) return;
            $.post('/admin/users/delete', {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: id
            }, function(res) {
                if (res.status == 'success') {
                    alert('Đã xóa user!');
                    location.reload();
                } else alert(res.message);
            });
        }

        // Withdrawal Actions
        function actionWithdrawal(id, action) {
            let actionText = action == 'approve' ? 'Duyệt lệnh rút tiền này' :
                'Từ chối lệnh rút tiền này (tiền sẽ được hoàn lại)';
            if (!confirm('Bạn chắc chắn muốn ' + actionText + '?')) return;

            $.post('/admin/withdrawals/' + action, {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: id
            }, function(res) {
                if (res.status == 'success') {
                    alert('Đã xử lý lệnh rút tiền!');
                    location.reload();
                } else alert(res.message);
            }).fail(function() {
                alert("Có lỗi CSRF hoặc timeout. Vui lòng tải lại trang.");
            });
        }

        function actionRemoveIp(id, action) {
            let actionText = action == 'approve' ? 'Xóa ip này' :
                'hủy';
            if (!confirm('Bạn chắc chắn muốn ' + actionText + '?')) return;

            $.post('/admin/removeIp/' + action, {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: id
            }, function(res) {
                if (res.status == 'success') {
                    alert('Đã xóa ip!');
                    location.reload();
                } else alert(res.message);
            }).fail(function() {
                alert("Có lỗi CSRF hoặc timeout. Vui lòng tải lại trang.");
            });
        }

        // Withdrawal Details
        let currentWdId = null;

        function viewWithdrawalDetails(id) {
            currentWdId = id;
            $('#btnExportWd').hide();
            $('#wdDetailModal').show();
            $('#wdDetailBody').html(
                '<div style="text-align:center; padding:30px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:32px; color:#2196f3;"></i><p>Đang tải dữ liệu...</p></div>'
            );

            $.get('/admin/withdrawal/' + id + '/details', function(res) {
                if (res.status === 'success') {
                    let txs = res.data.transactions;
                    if (txs.length === 0) {
                        $('#wdDetailBody').html(
                            '<p style="text-align:center; color:#999; padding:20px;">Không tìm thấy giao dịch nào được liên kết.</p>'
                        );
                        return;
                    }

                    let html = `
                <div style="margin-bottom:15px; background:#e3f2fd; padding:10px; border-radius:8px; border-left:4px solid #2196f3;">
                    <b>Lệnh rút ID: #${id}</b><br>
                    Tổng số đơn: <b>${txs.length}</b> đơn
                </div>
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="background:#f1f5f9; text-align:left;">
                            <th style="padding:10px; border:1px solid #eee;">Mã giao dịch</th>
                            <th style="padding:10px; border:1px solid #eee;">VA Number</th>
                            <th style="padding:10px; border:1px solid #eee;">Số tiền</th>
                            <th style="padding:10px; border:1px solid #eee;">Thực nhận</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${txs.map(tx => `
                                                <tr>
                                                    <td style="padding:10px; border:1px solid #eee; font-family:monospace;">${tx.tx_id}</td>
                                                    <td style="padding:10px; border:1px solid #eee;">${tx.va_number}</td>
                                                    <td style="padding:10px; border:1px solid #eee;">${new Intl.NumberFormat().format(tx.amount)} đ</td>
                                                    <td style="padding:10px; border:1px solid #eee; color:green; font-weight:bold;">${new Intl.NumberFormat().format(tx.actual_amount)} đ</td>
                                                </tr>
                                            `).join('')}
                    </tbody>
                </table>
            `;
                    $('#wdDetailBody').html(html);
                    $('#btnExportWd').show();
                } else {
                    $('#wdDetailBody').html('<p style="color:red; text-align:center;">' + res.message + '</p>');
                }
            }).fail(function() {
                $('#wdDetailBody').html(
                    '<p style="color:red; text-align:center;">Lỗi hệ thống hoặc phiên làm việc đã hết hạn.</p>');
            });
        }




        // Mass Create VA Actions
        let lastCreatedVAs = [];

        $('.bank').on('click', function() {
            $('.bank').removeClass('active');
            $(this).addClass('active');
            $('#admin_bank').val($(this).data('bank'));
        });

        $('#massCreateBtn').on('click', function() {
            let btn = $(this);
            let bank = $('#admin_bank').val();
            let prefix = $('#admin_prefix').val();
            let quan = $('#admin_quan').val();
            let name_length = $('#admin_name_length').val();
            let user_id = $('#mass_user_id').val();
            let account_length = $('#admin_account_length').val() || 10;

            if (!bank || !prefix || !quan || !user_id) {
                return alert("Vui lòng nhập đầy đủ: User, Ngân hàng, Prefix và Số lượng.");
            }

            // if (quan > 100) return alert("Số lượng phải <= 100 !");

            btn.html('<i class="fa-solid fa-spinner fa-spin"></i> ĐANG XỬ LÝ...').prop('disabled', true);
            $('#resultBox').hide();
            $('#exportCsvBtn').hide();
            lastCreatedVAs = [];

            $.post('/admin/mass-create', {
                _token: $('meta[name="csrf-token"]').attr('content'),
                bank: bank,
                prefix: prefix,
                quantity: quan,
                name_length: name_length,
                user_id: user_id,
                account_length: account_length
            }, function(res) {
                if (res.status === 'success') {
                    $('#countSuccess').text(res.data.created_count);
                    $('#countFail').text(res.data.failed_count);

                    lastCreatedVAs = res.data.created_list;
                    if (lastCreatedVAs.length > 0) {
                        $('#exportCsvBtn').show();
                    }

                    let htmlSuccess = lastCreatedVAs.map(i =>
                        `<div style="padding: 8px 0; border-bottom: 1px dotted #e0e0e0;"><i class="fa-regular fa-circle-check" style="color:green; margin-right:8px;"></i>${i.merchant_name} - <b style="color:#e91e63">${i.va_number}</b></div>`
                    ).join('');
                    $('#listSuccess').html(htmlSuccess || '<i>Không có</i>');

                    let htmlFail = res.data.failed_list.map(i =>
                        `<div style="padding: 8px 0; border-bottom: 1px dotted #e0e0e0;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:8px;"></i>${i}</div>`
                    ).join('');
                    $('#listFail').html(htmlFail || '<i>Không có</i>');

                    $('#resultBox').fadeIn();
                } else {
                    alert("Lỗi: " + res.message);
                }
            }).fail(function() {
                alert("Lỗi máy chủ/Timeout khi gọi API.");
            }).always(function() {
                btn.html('<i class="fa-solid fa-rocket"></i> BẮT ĐẦU TẠO HÀNG LOẠT').prop('disabled',
                    false);
            });
        });

        // CSV Export Logic
        $('#exportCsvBtn').on('click', function() {
            if (lastCreatedVAs.length === 0) return;

            // Add BOM for Excel UTF-8 support
            let csvContent = "\uFEFF";
            csvContent += "Tên Tài Khoản,Số Tài Khoản,Ngân Hàng\n";

            lastCreatedVAs.forEach(function(item) {
                // Escape quotes if any
                let name = item.merchant_name.replace(/"/g, '""');
                let vaNum = item.va_number.replace(/"/g, '""');
                let bank = (item.bank || $('#admin_bank').val()).replace(/"/g, '""');
                // Add tab character in front of va_number to prevent Excel from converting it to exponential form
                csvContent += `"${name}","\t${vaNum}","${bank}"\n`;
            });

            let blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            let url = URL.createObjectURL(blob);
            let link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", "virtual_accounts_" + new Date().getTime() + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        $('#btnExportWd').on('click', function() {
            if (!currentWdId) return;
            window.location.href = '/admin/withdrawal/' + currentWdId + '/export';
        });

        $('#tao_theo_yeu_cau').on('click', function() {

            let formData = new FormData();
            let file = $('#tao_file')[0].files[0];

            if (!file) {
                alert('Vui lòng chọn file');
                return;
            }

            formData.append('file', file);
            formData.append('user_id', $('#tao_theo_yeu_cau_user_id').val());
            formData.append('bank', $('.bank.active').data('bank'));
            formData.append('account_length', $('#tao_account_length').val() || 10);

            $.ajax({
                url: '/admin/tao-theo-yeu-cau-create',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    if (res.status === 'success') {

                        $('#countSuccess').text(res.data.created_count);
                        $('#countFail').text(res.data.failed_count);

                        $('#listSuccess').html(
                            res.data.created_list.map(i =>
                                `<div>${i.merchant_name} - <b>${i.va_number}</b></div>`
                            ).join('')
                        );

                        $('#listFail').html(
                            res.data.failed_list.map(i =>
                                `<div style="color:red">${i}</div>`
                            ).join('')
                        );

                        $('#resultBox').show();
                        $('#btnExportTaoYeuCau').show();

                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    alert('Lỗi server');
                }
            });

        });

        $('#btnExportTaoYeuCau').on('click', function() {
            window.location.href = '/admin/tao-theo-yeu-cau-export';
        });
    </script>


    <script>
        // =============================================
        //  SPEECH QUEUE — không chồng âm
        // =============================================
        const speechQueue = [];
        let isSpeaking = false;

        function speak(text) {
            if (!('speechSynthesis' in window)) return;
            speechQueue.push(text);
            processQueue();
        }

        function processQueue() {
            if (isSpeaking || speechQueue.length === 0) return;
            isSpeaking = true;
            const text = speechQueue.shift();
            const msg = new SpeechSynthesisUtterance();
            msg.text = text;
            msg.lang = 'vi-VN';
            msg.rate = 1;
            msg.pitch = 1;
            msg.onend = function() {
                isSpeaking = false;
                processQueue();
            };
            msg.onerror = function() {
                isSpeaking = false;
                processQueue();
            };
            window.speechSynthesis.speak(msg);
        }
    </script>

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                speak("{{ session('success') }}");
            });
        </script>
    @endif

    @if ($tab == 'withdrawals')
        <script>
            // =============================================
            //  REAL-TIME POLLING — hiển thị lệnh rút mới
            // =============================================
            document.addEventListener('DOMContentLoaded', function() {

                // Lấy ID lớn nhất hiện tại trong bảng
                let lastKnownId = 0;
                document.querySelectorAll('#sec-withdrawals tbody tr[id^="with-row-"]').forEach(function(row) {
                    const id = parseInt(row.id.replace('with-row-', ''));
                    if (id > lastKnownId) lastKnownId = id;
                });

                function fmt(n) {
                    return new Intl.NumberFormat('vi-VN').format(n);
                }

                function buildRow(w) {
                    const userName = w.user_name || ('User #' + w.user_id);
                    const tgLink = w.user_tg ?
                        '<a href="https://t.me/' + w.user_tg +
                        '" target="_blank" style="text-decoration:none;color:#2196f3;font-size:13px;font-weight:bold;"><i class="fa-brands fa-telegram"></i> #' +
                        w.user_id + '</a>' :
                        '<span style="color:#999;">#' + w.user_id + '</span>';
                    const qrHtml = w.qr_code ?
                        '<div style="margin-top:8px;"><a href="/' + w.qr_code + '" target=""><img src="/' + w.qr_code +
                        '" style="width:50px;height:50px;object-fit:cover;border-radius:4px;border:1px solid #ddd;"></a></div>' :
                        '';
                    return '<tr id="with-row-' + w.id + '" style="background:#fffde7; transition: background 1s;">' +
                        '<td>' + w.id + '</td>' +
                        '<td><div style="font-weight:700;color:#333;">' + userName + '</div>' + tgLink + '</td>' +
                        '<td><strong style="color:#e91e63;">' + fmt(w.amount) + ' đ</strong></td>' +
                        '<td>' + w.bank + '</td>' +
                        '<td style="font-size:14px;"><div style="font-weight:700;color:#e91e63;">' + w.stk +
                        '</div><div style="color:#666;font-size:13px;margin-top:3px;">' + w.name + '</div>' + qrHtml +
                        '</td>' +
                        '<td><span class="badge pending" id="status-with-' + w.id + '">Chờ Duyệt</span></td>' +
                        '<td id="action-with-' + w.id + '">' +
                        '<div style="display:flex;flex-direction:column;gap:8px;">' +
                        '<button class="action-btn btn-approve" onclick="actionWithdrawal(' + w.id +
                        ',\'approve\')"><i class="fa-solid fa-check"></i> Duyệt</button>' +
                        '<button class="action-btn btn-delete" onclick="actionWithdrawal(' + w.id +
                        ',\'reject\')"><i class="fa-solid fa-xmark"></i> Từ chối</button>' +
                        '<button class="action-btn btn-toggle" onclick="viewWithdrawalDetails(' + w.id +
                        ')" style="background:#2196f3;"><i class="fa-solid fa-circle-info"></i> Chi tiết</button>' +
                        '</div></td>' +
                        '</tr>';
                }

                function pollWithdrawals() {
                    $.getJSON('/admin/withdrawals/latest?after_id=' + lastKnownId, function(res) {
                        if (res.status !== 'success' || !res.data || res.data.length === 0) return;

                        // Sắp xếp tăng dần để prepend đúng thứ tự
                        const sorted = res.data.slice().sort(function(a, b) {
                            return a.id - b.id;
                        });
                        const tbody = document.querySelector('#sec-withdrawals tbody');

                        sorted.forEach(function(w) {
                            if (w.id > lastKnownId) lastKnownId = w.id;

                            // Bỏ qua nếu row đã tồn tại
                            if (document.getElementById('with-row-' + w.id)) return;

                            // Thêm row mới vào đầu bảng
                            tbody.insertAdjacentHTML('afterbegin', buildRow(w));

                            // Xóa highlight vàng sau 5 giây
                            setTimeout(function() {
                                const row = document.getElementById('with-row-' + w.id);
                                if (row) row.style.background = '';
                            }, 5000);

                            // Phát thông báo giọng nói
                            const name = w.user_name || ('user số ' + w.user_id);
                            speak('Cảnh báo! Có lệnh rút tiền mới từ ' + name + ', số tiền ' + fmt(w
                                .amount) + ' đồng.');
                        });
                    });
                }

                // Polling mỗi 10 giây
                setInterval(pollWithdrawals, 10000);
            });
        </script>
    @endif

    @if ($tab == 'broadcast')
        <div class="section-content active">
            <h3 style="margin-top:0;">Gửi Thông Báo Hàng Loạt</h3>
            <p style="color: #666; margin-bottom: 25px;">Tin nhắn sẽ được gửi qua Telegram cho toàn bộ người dùng trong hệ thống (sử dụng Queue).</p>

            <div style="max-width: 800px; background: #fafafa; padding: 25px; border-radius: 12px; border: 1px solid #eee;">
                <div class="form-group">
                    <label>Nội dung thông báo (hỗ trợ HTML tối thiểu):</label>
                    <textarea id="broadcast_message" rows="8" 
                        style="width: 100%; padding: 15px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-family: inherit; font-size: 15px;"
                        placeholder="Nhập nội dung thông báo..."></textarea>
                </div>
                <button class="btn-primary" onclick="sendBroadcast()" id="btnSendBroadcast">
                    <i class="fa-solid fa-paper-plane"></i> GỬI NGAY
                </button>
            </div>
        </div>

        <script>
            function sendBroadcast() {
                const message = document.getElementById('broadcast_message').value;
                if (!message) return Swal.fire('Lỗi', 'Vui lòng nhập nội dung', 'error');

                const btn = document.getElementById('btnSendBroadcast');
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

                fetch('/admin/broadcast', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: message })
                })
                .then(response => response.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (res.status === 'success') {
                        Swal.fire('Thành công', res.message, 'success');
                        document.getElementById('broadcast_message').value = '';
                    } else {
                        Swal.fire('Lỗi', res.message, 'error');
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    Swal.fire('Lỗi', 'Có lỗi xảy ra trong quá trình gửi.', 'error');
                });
            }
        </script>
    @endif
 
    @if ($tab == 'proxy-settings')
        <div class="section-content active">
            <h3 style="margin-top:0;">Cấu hình Proxy</h3>
            <p style="color: #666; margin-bottom: 25px;">Nhập danh sách proxy để hệ thống sử dụng (mỗi proxy một dòng, định dạng: <code>http://username:password@ip:port</code> hoặc <code>http://ip:port</code>).</p>

            <div style="max-width: 800px; background: #fafafa; padding: 25px; border-radius: 12px; border: 1px solid #eee;">
                <div class="form-group">
                    <label>Danh sách Proxy:</label>
                    <textarea id="proxy_settings" rows="8" 
                        style="width: 100%; padding: 15px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-family: inherit; font-size: 15px;"
                        placeholder="http://ip:port...">{{ $telegram_proxies }}</textarea>
                </div>
                <button class="btn-primary" onclick="saveProxySettings()" id="btnSaveProxy">
                    <i class="fa-solid fa-save"></i> LƯU CẤU HÌNH
                </button>
            </div>
        </div>

        <script>
            function saveProxySettings() {
                const proxies = document.getElementById('proxy_settings').value;
                const btn = document.getElementById('btnSaveProxy');
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

                fetch('/admin/proxy-settings/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ proxies: proxies })
                })
                .then(response => response.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (res.status === 'success') {
                        alert(res.message);
                    } else {
                        alert(res.message);
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    alert('Có lỗi xảy ra trong quá trình lưu.');
                });
            }
        </script>
    @endif
 
</body>

</html>
