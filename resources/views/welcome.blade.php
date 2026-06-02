<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bánh </title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('layout._style_css')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .terms-box {
            transition: 0.3s;
        }

        .terms-box:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }


        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>

</head>

<body>

<div class="container">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">Tạo bánh</div>

        <div class="user">
            <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name }}&background=4facfe&color=fff&bold=true"
                 alt="Avatar">
            <div>
                <p>{{ auth()->user()->name ?? 'User' }}</p>
                <small style="color:var(--text-muted); font-size:11px;">Hội viên Chính thức</small>
            </div>
        </div>

        @include('layout.menu')
    </div>

    <!-- Main -->
    <div class="main">
        @include('layout.logout')

        <!-- Tab Tạo Bank -->
        @if ($tab == 'tab-create')
            <div class="tab-pane" id="tab-create">

                <div class="stats-container">
                    <div class="stat-card">
                        <i class="fas fa-chart-line"></i>
                        <h4>Tiền thu 24h</h4>
                        <div class="value">{{ number_format($amount24h, 0, ',', '.') }} đ</div>
                    </div>
                    <div class="stat-card withdrawn">
                        <i class="fas fa-arrow-up-right-from-square"></i>
                        <h4>Tổng đã rút</h4>
                        <div class="value">{{ number_format($totalWithdrawn, 0, ',', '.') }} đ</div>
                    </div>
                    <div class="stat-card balance">
                        <i class="fas fa-wallet"></i>
                        <h4>Số dư còn lại</h4>
                        <div class="value" id="topBalanceDisplay">
                            {{ number_format($so_tien_con_lai, 0, ',', '.') }} đ
                        </div>
                    </div>
                </div>


                <div id="termsPopup" style="
                                    position:fixed;
                                    top:0;left:0;
                                    width:100%;height:100%;
                                    background:rgba(0,0,0,0.5);
                                    display:none;
                                    align-items:center;
                                    justify-content:center;
                                    z-index:9999;
                                ">
                    <div style="
                                        background:#fff;
                                        width:90%;
                                        max-width:500px;
                                        border-radius:12px;
                                        padding:20px;
                                        text-align:center;
                                        animation:fadeIn 0.3s;
                                    ">
                        <h4 style="margin-bottom:10px;font-weight:600;">
                            <i class="fas fa-file-contract"></i> Điều khoản & điều kiện
                        </h4>

                        <ul style="text-align:left;padding-left:18px;line-height:1.8;color:red;">
                            <li>Cấm mọi hành vi lừa đảo dưới mọi hình thức</li>
                            <li>Không chia sẻ tài khoản</li>
                            <li>Không phát tán thông tin</li>
                            <li>Vi phạm: khóa tài khoản, giữ tiền</li>
                        </ul>

                        <button onclick="closeTerms()" style="
                                            margin-top:15px;
                                            padding:10px 20px;
                                            background:#ef4444;
                                            color:#fff;
                                            border:none;
                                            border-radius:8px;
                                            cursor:pointer;
                                        ">
                            Tôi đã hiểu
                        </button>
                    </div>
                </div>


                <div class="card">

                    <h2 style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-plus-circle" style="color:var(--primary);"></i>
                        Tạo Tài Khoản
                    </h2>

                    <div class="tab-content active" id="single">
                        <label>Nhập tên</label>
                        <input type="text" id="va_name" placeholder="Le VAN A">
                        <div id="va_preview" style="margin-top:6px; font-size:13px; color:#888;">
                            Tên : -
                        </div>
                        <button class="btn" id="createSingle" style="margin-top: 20px;">Tạo tài khoản & sinh QR</button>
                    </div>

                    <div id="result" style="margin-top:15px;"></div>
                </div>

                <!-- History -->
                <div class="history">
                    <h3 style="margin-bottom:20px; font-size:18px; font-weight:700; color:var(--text-main);">
                        <i class="fas fa-history" style="margin-right:10px;"></i> Lịch sử tạo tài khoản gần đây
                    </h3>
                    <div id="historyList">
                        <div style="text-align:center; padding:40px; color:var(--text-muted);">
                            <i class="fas fa-spinner fa-spin" style="font-size:24px; margin-bottom:10px;"></i>
                            <p>Đang tải lịch sử...</p>
                        </div>
                    </div>
                </div>
            </div> <!-- End tab-create -->
        @endif

        <!-- Tab Profile -->
        @if ($tab == 'tab-profile')
            <div class="tab-pane" id="tab-profile">
                <div class="card">
                    <h2 style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-id-card" style="color:var(--primary);"></i>
                        Tài Khoản Liên Kết Của Bạn
                    </h2>
                    <div id="bankList" class="bank-card-container" style="margin-bottom:40px;">
                        <p style="color:var(--text-muted);">Đang tải dữ liệu...</p>
                    </div>

                    <h2
                            style="display:flex; align-items:center; gap:10px; border-top:1px solid var(--border-color); padding-top:30px;">
                        <i class="fas fa-plus-square" style="color:var(--primary);"></i>
                        <span id="profFormTitle">Thêm Tài Khoản Nhận Tiền</span>
                    </h2>
                    <input type="hidden" id="prof_id">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                        <div>
                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Tên
                                chủ tài khoản</label>
                            <input type="text" id="prof_name" placeholder="VD: NGUYEN VAN A">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Ngân
                                hàng</label>
                            <input type="text" id="prof_bank" placeholder="VD: Vietcombank">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                        <div>
                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Số
                                tài
                                khoản (STK)</label>
                            <input type="text" id="prof_stk" placeholder="Nhập số tài khoản">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Ảnh
                                QR
                                (nếu có)</label>
                            <input type="file" id="prof_qr" accept="image/*" style="padding:10px;">
                        </div>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button class="btn" id="saveProfBtn">LƯU TÀI KHOẢN</button>
                        <button class="btn" id="cancelEditBtn" style="display:none; background:#64748b;">HỦY
                            BỎ</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tab Withdraw -->
        @if ($tab == 'tab-withdraw')
            <div class="tab-pane" id="tab-withdraw">
                <div class="card" style="max-width:600px; margin:0 auto;">
                    <h2
                            style="display:flex; align-items:center; gap:10px; border-bottom:1px solid #fee2e2; padding-bottom:15px; color:#ef4444;">
                        <i class="fas fa-money-bill-transfer"></i>
                        Yêu Cầu Thanh Khoản
                    </h2>

                    <div
                            style="background:#fff1f2; border:1px solid #fecdd3; padding:15px; border-radius:12px; margin:20px 0;">
                        <p style="color:#e11d48; font-size:13px; margin:0; line-height:1.6;">
                            <i class="fas fa-circle-exclamation" style="margin-right:5px;"></i>
                            <b>Lưu ý:</b> Rút tiền nhắn với admin để được duyệt <b> <a style="text-decoration: none;"
                                                                                       href="https://t.me/botcamap">Đàm cá mập</a></b>
                        </p>
                    </div>

                    <div style="margin-bottom:25px;">
                        <label style="display:block; margin-bottom:10px; font-weight:600;">Chọn TK nhận
                            tiền:</label>
                        <select id="wd_bank_id" style="font-weight:600;"></select>
                    </div>

                    <div
                            style="background:var(--bg-main); padding:25px; border-radius:15px; text-align:center; margin-bottom:30px; border:1px dashed var(--primary);">
                        <p
                                style="margin:0 0 10px 0; font-size:14px; font-weight:600; color:var(--text-muted); text-transform:uppercase;">
                            Số dư có thể rút</p>
                        <div id="statBalanceWithdraw" style="font-size:36px; font-weight:800; color:var(--primary);">0 đ
                        </div>
                    </div>

                    @if (isset($unredeemedTransactions) && count($unredeemedTransactions) > 0)
                        <div style="margin-bottom:20px;">
                            <div
                                    style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                <h4 style="margin:0; font-size:14px; font-weight:700; color:#334155;">Chi tiết các
                                    mã chưa thanh khoản:</h4>
                                <a href="/user/transactions/export-unredeemed" class="btn"
                                   style="padding:4px 8px; font-size:10px; width:auto; background:#10b981; text-transform:none; border-radius:6px; height:auto; line-height:normal;">
                                    <i class="fas fa-file-excel"></i> Xuất Excel
                                </a>
                            </div>
                            <div
                                    style="max-height: 250px; overflow-y: auto; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; padding: 10px;">
                                @foreach ($unredeemedTransactions as $tx)
                                    <div
                                            style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f1f5f9;">
                                        <div style="text-align:left;">
                                            <div style="font-size:13px; font-weight:700; color:#1e293b;">
                                                {{ number_format($tx->actual_amount) }} đ
                                            </div>


                                            @php

                                                $masked = \Illuminate\Support\Str::mask($tx->va_number, '*', 0, 12);

                                            @endphp

                                            <div style="font-size:11px; color:#64748b;">VA: {{ $masked }}
                                            </div>
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="font-size:11px; font-family:monospace; color:#94a3b8;">
                                                {{ $tx->tx_id }}
                                            </div>
                                            <div style="font-size:10px; color:#cbd5e1;">
                                                {{ \Carbon\Carbon::parse($tx->completion_time)->format('H:i d/m') }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <button class="btn" id="drawBtn"
                            style="background:#ef4444; box-shadow:0 10px 20px rgba(239, 68, 68, 0.2);">
                        XÁC NHẬN RÚT TIỀN NGAY
                    </button>

                    <!-- LỊCH SỬ RÚT TIỀN -->
                    <h3
                            style="margin-top:40px; margin-bottom:20px; font-size:18px; font-weight:700; color:var(--text-main);">
                        <i class="fas fa-history" style="margin-right:10px; color:#ef4444;"></i> Lịch sử rút tiền
                        gần đây
                    </h3>
                    <div id="wdHistoryList">
                        @if (count($withdrawals) > 0)
                            @foreach ($withdrawals as $w)
                                <div class="history-item"
                                     style="cursor: default; border-left: 4px solid @if ($w->status == 1) #10b981 @else #f59e0b @endif; position: relative;">
                                    <div style="flex: 1;">
                                        <b style="color: #e11d48; font-size: 16px;">-{{ number_format($w->amount) }}
                                            đ</b>
                                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                            {{ $w->bank }} - {{ $w->stk }}
                                        </div>
                                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                            {{ $w->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                    <div
                                            style="text-align: right; display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                        @if ($w->status == 1)
                                            <span class="badge success"
                                                  style="background: #ecfdf5; color: #059669; border: 1px solid #10b981; display: inline-block;">Hoàn
                                                    thành</span>
                                        @elseif($w->status == 2 || $w->status === 'rejected')
                                            <span class="badge danger"
                                                  style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; display: inline-block;">Từ
                                                    chối</span>
                                        @else
                                            <span class="badge pending"
                                                  style="background: #fffbeb; color: #d97706; border: 1px solid #f59e0b; display: inline-block;">Đang
                                                    xử lý</span>
                                        @endif
                                        <button onclick="viewWithdrawalDetails({{ $w->id }})"
                                                style="border: none; background: #3b82f6; color: #fff; border-radius: 6px; padding: 5px 12px; font-size: 11px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-info-circle"></i> Chi tiết
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div
                                    style="text-align:center; padding:40px; background:#f8fafc; border-radius:16px; color:#94a3b8;">
                                Bạn chưa có yêu cầu rút tiền nào.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- Detail Modal (Re-using vaModal structure) -->
    <div id="vaModal"
         style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.8); backdrop-filter:blur(4px);">
        <div
                style="background:#fff; width:90%; max-width:400px; margin: 0px auto; padding:30px; border-radius:24px; text-align:center; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); animation:fadeIn 0.3s ease; position:relative;">
            <div style="position:absolute; top:20px; right:20px; cursor:pointer; color:var(--text-muted);"
                 id="closeModalIcon">
                <i class="fas fa-times" style="font-size:20px;"></i>
            </div>

            <div id="modalIconContainer"
                 style="background:#f0fdf4; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
                <i class="fas fa-check-circle" style="color:#10b981; font-size:30px;"></i>
            </div>

            <h3 id="modalTitle"
                style="color:#1e293b; margin-top:0; font-size:20px; font-weight:800; margin-bottom:5px;">Chi Tiết
                Tài Khoản</h3>
            <p id="modalSubTitle" style="color:var(--text-muted); font-size:13px; margin-bottom:20px;">Thông tin
                tài khoản định danh của bạn</p>

            <div id="modalQrContainer" style="margin-bottom:20px; display:none;">
                <img id="modalQrImage" src=""
                     style="width:160px; height:160px; border:1px solid #e2e8f0; border-radius:12px; padding:5px; background:#fff;">
            </div>

            <div style="background:#f8fafc; padding:20px; border-radius:16px; text-align:left; margin-bottom:25px;">

                <!-- STK -->
                <div class="detail-row">

                        <span class="detail-value">
                            <span id="modalVa"></span>
                            <i class="fas fa-copy copy-btn" onclick="copyText('modalVa','Số tài khoản')"></i>
                        </span>
                </div>

                <!-- Chủ TK -->
                <div class="detail-row">

                        <span class="detail-value">
                            <span id="modalName"></span>
                            <i class="fas fa-copy copy-btn" onclick="copyText('modalName','Chủ tài khoản')"></i>
                        </span>
                </div>


                <div class="detail-row">
                    <span class="detail-label">Mã đơn hàng</span>
                    <span class="detail-value" id="modalma_don_hang"></span>
                </div>

                <!-- Bank -->
                <div class="detail-row">
                    <span class="detail-label">Ngân hàng</span>
                    <span class="detail-value" id="modalBank"></span>
                </div>

                <!-- Fee -->
                <div class="detail-row">
                    <span class="detail-label">Chiết khấu (Fee)</span>
                    <span class="detail-value" id="modalFee" style="color:#10b981;"></span>
                </div>



            </div>

            <button id="closeModal" class="btn">ĐÓNG</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/utils.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>

    @yield('scripts')

    <div id="wdDetailModal" class="modal-overlay"
         style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-content"
             style="background:#fff; width:95%; max-width:600px; max-height:80vh; border-radius:20px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
            <div
                    style="padding:20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:18px; font-weight:800; color:#1e293b;">
                    <i class="fas fa-file-invoice-dollar" style="margin-right:10px; color:var(--primary);"></i>
                    Chi tiết lệnh rút tiền
                </h3>
                <button onclick="$('#wdDetailModal').hide()"
                        style="border:none; background:none; font-size:28px; cursor:pointer; color:#94a3b8;">&times;</button>
            </div>
            <div id="wdDetailBody" style="padding:20px; overflow-y:auto; flex:1;">
                <div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin"
                                                                 style="font-size:32px; color:var(--primary);"></i>
                    <p style="margin-top:15px; color:#64748b;">Đang lấy dữ liệu...</p>
                </div>
            </div>
            <div
                    style="padding:15px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; text-align:right; display:flex; justify-content:space-between; align-items:center;">
                <a id="btnExportUserWd" href="#" class="btn"
                   style="padding:10px 20px; background:#10b981; color:#fff; font-size:13px; border-radius:12px; display:none;"><i
                            class="fas fa-file-excel"></i> Xuất Excel</a>
                <button class="btn" onclick="$('#wdDetailModal').hide()"
                        style="padding:10px 25px; background:#64748b; color:#fff; font-size:14px; border-radius:12px;">Đóng</button>
            </div>
        </div>
    </div>

</div>
<script>
    const input = document.getElementById("va_name");
    const preview = document.getElementById("va_preview");

    function removeVietnameseTones(str) {
        return str.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/Đ/g, 'D');
    }

    input.addEventListener("input", function () {
        let raw = this.value;
        let name = removeVietnameseTones(raw).toUpperCase().trim();
        preview.innerText = "Tên : " + (name || "-");
    });
</script>




</body>

</html>