let historyData = [];
let userBanks = [];

window.showVaDetails = function (index) {
    const item = historyData[index];
    if (!item) return;

    $('#modalTitle').text('Chi Tiết Tài Khoản');
    $('#modalSubTitle').text('Thông tin định danh của bạn');
    $('#modalIconContainer').html(
        '<i class="fas fa-info-circle" style="color:var(--primary); font-size:30px;"></i>'
    ).css('background', '#f0f9ff');
    $('#modalBank').text(item.bank);
    $('#modalVa').text(item.va_number);
    $('#modalma_don_hang').text(item.ma_don_hang);
    $('#modalName').text(item.merchant_name);
    $('#modalFee').text('CK: ' + (item.fee_rate || 8) + '%');

    if (item.quick_link) {
        $('#modalQrImage').attr('src', item.quick_link);
        $('#modalQrContainer').show();
    } else {
        $('#modalQrContainer').hide();
    }

    $('#vaModal').fadeIn();
};

window.loadHistory = function () {
    $.get('/va/history', function (res) {
        historyData = res.data;
        let html = '';
        if (res.data.length === 0) {
            html = '<div style="text-align:center; padding:40px; color:var(--text-muted);">Bạn chưa có lịch sử giao dịch VA nào.</div>';
        } else {
            res.data.forEach((item, index) => {
                html += `
                    <div class="history-item" onclick="showVaDetails(${index})">
                        <div>
                            <b>${item.merchant_name}</b>
                            <div class="bank-name">${item.bank}</div>
                        </div>
                        <i class="fas fa-chevron-right" style="color:var(--border-color);"></i>
                    </div>
                `;
            });
        }
        $('#historyList').html(html);
    });
};

window.loadStats = function () {
    $.get('/user/stats', function (res) {
        userBanks = res.banks || [];
        let bal = parseFloat(res.diem || 0).toLocaleString('vi-VN');

        $('#statBalanceWithdraw').text(bal + ' đ');
        $('#topBalanceDisplay').text(bal + ' đ');

        let htmlBanks = '';
        let selectOptions = '<option value="">-- Chọn tài khoản nhận tiền --</option>';

        if (userBanks.length > 0) {
            userBanks.forEach((bank, index) => {
                let qrImg = bank.qr_code ?
                    `<img src="${bank.qr_code}" class="bank-qr-preview" alt="QR" onclick="window.open('${bank.qr_code}')">` :
                    '';
                htmlBanks += `
                <div class="bank-item-card">
                    <div style="display:flex; align-items:center; gap:15px; flex:1;">
                        ${qrImg}
                        <div class="bank-info">
                            <h5>${bank.bank}</h5>
                            <p><b>STK:</b> ${bank.stk} | <b>Chủ:</b> ${bank.name}</p>
                        </div>
                    </div>
                    <div class="bank-actions">
                        <div class="action-btn edit" onclick="editBank(${index})" title="Sửa">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                        <div class="action-btn delete" onclick="deleteBank(${bank.id})" title="Xóa">
                            <i class="fas fa-trash-alt"></i>
                        </div>
                    </div>
                </div>`;

                selectOptions += `<option value="${bank.id}">${bank.bank} - ${bank.stk} (${bank.name})</option>`;
            });
        } else {
            htmlBanks = '<div style="grid-column:1/-1; text-align:center; padding:40px; background:var(--bg-main); border-radius:16px; color:var(--text-muted);">Bạn chưa liên kết tài khoản ngân hàng nào.</div>';
        }

        $('#bankList').html(htmlBanks);
        $('#wd_bank_id').html(selectOptions);
    });
};

window.editBank = function (index) {
    const bank = userBanks[index];
    if (!bank) return;

    $('#prof_id').val(bank.id);
    $('#prof_name').val(bank.name);
    $('#prof_bank').val(bank.bank);
    $('#prof_stk').val(bank.stk);

    $('#profFormTitle').text('Sửa Tài Khoản Nhận Tiền');
    $('#saveProfBtn').text('CẬP NHẬT TÀI KHOẢN');
    $('#cancelEditBtn').show();

    $('html, body').animate({
        scrollTop: $("#profFormTitle").offset().top - 100
    }, 500);
};

window.deleteBank = function (id) {
    if (!confirm("Bạn có chắc chắn muốn xóa tài khoản ngân hàng này?")) return;

    $.post('/user/bank/delete', {
        _token: $('meta[name="csrf-token"]').attr('content'),
        id: id
    }, function (res) {
        if (res.status === 'success') {
            alert('Đã xóa thành công!');
            loadStats();
        } else {
            alert(res.message);
        }
    });
};

window.resetProfForm = function () {
    $('#prof_id').val('');
    $('#prof_name').val('');
    $('#prof_bank').val('');
    $('#prof_stk').val('');
    $('#prof_qr').val('');
    $('#profFormTitle').text('Thêm Tài Khoản Nhận Tiền');
    $('#saveProfBtn').text('LƯU TÀI KHOẢN');
    $('#cancelEditBtn').hide();
};

window.viewWithdrawalDetails = function (id) {
    $('#wdDetailModal').show();
    $('#wdDetailBody').html(
        '<div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:32px; color:var(--primary);"></i><p style="margin-top:15px; color:#64748b;">Đang lấy dữ liệu...</p></div>'
    );

    $.get('/user/withdrawal/' + id + '/details', function (res) {
        if (res.status === 'success') {
            let txs = res.data.transactions;
            let wdId = res.data.withdrawal.id;
            $('#btnExportUserWd').attr('href', '/user/withdrawal/' + wdId + '/export').show();
            if (txs.length === 0) {
                $('#wdDetailBody').html('<p style="color:#94a3b8; text-align:center; padding:20px;">Không có giao dịch nào.</p>');
                return;
            }
            let html = `
                <div style="background:#f8fafc; padding:15px; border-radius:16px; margin-bottom:20px; border:1px solid #f1f5f9;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span style="color:#64748b; font-size:13px;">Lệnh rút:</span>
                        <b style="color:#1e293b;">#${wdId}</b>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:#64748b; font-size:13px;">Số lượng đơn:</span>
                        <b style="color:var(--primary);">${txs.length} đơn</b>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    ${txs.map(tx => `
                        <div style="background:#fff; border:1px solid #f1f5f9; padding:12px; border-radius:12px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:14px; font-weight:700; color:#334155;">${new Intl.NumberFormat().format(tx.actual_amount)} đ</div>
                                <div style="font-size:11px; color:#94a3b8; margin-top:2px;">VA: ${tx.va_number}</div>
                            </div>
                            <div style="font-size:11px; font-family:monospace; color:#94a3b8; background:#f8fafc; padding:4px 8px; border-radius:6px;">
                                ${tx.tx_id}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            $('#wdDetailBody').html(html);
        } else {
            $('#wdDetailBody').html('<p style="color:#ef4444; text-align:center; padding:20px;">' + res.message + '</p>');
        }
    }).fail(function () {
        $('#wdDetailBody').html('<p style="color:#ef4444; text-align:center; padding:20px;">Lỗi kết nối. Vui lòng thử lại sau.</p>');
    });
};

window.showTermsIfNeeded = function () {
    let lastShown = localStorage.getItem("terms_last_shown");
    let now = new Date().getTime();
    if (!lastShown || (now - lastShown > 86400000)) {
        document.getElementById("termsPopup").style.display = "flex";
    }
};

window.closeTerms = function () {
    localStorage.setItem("terms_last_shown", new Date().getTime());
    document.getElementById("termsPopup").style.display = "none";
};

$(document).ready(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Tab Switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            let tab = this.getAttribute('data-tab');
            let target = document.getElementById(tab);
            if (target) target.classList.add('active');
        });
    });

    let btnSingle = document.getElementById('createSingle');
    if (btnSingle) {
        btnSingle.onclick = function () {
            let name = document.getElementById('va_name').value;
            if (!name) return alert('Vui lòng nhập tên');
            name = normalizeName(name);
            btnSingle.disabled = true;
            btnSingle.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo...';
            
            // Redirect to test-qr route via POST
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = '/bidv/test-qr';
            
            let csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrf;
            form.appendChild(csrfInput);
            
            let nameInput = document.createElement('input');
            nameInput.type = 'hidden';
            nameInput.name = 'name';
            nameInput.value = name;
            form.appendChild(nameInput);
            
            document.body.appendChild(form);
            form.submit();
        };
    }

    let btnMulti = document.getElementById('createMulti');
    if (btnMulti) {
        btnMulti.onclick = function () {
            let raw = document.getElementById('va_names').value;
            let bank = document.getElementById('bank_multi').value;
            let accountLength = document.getElementById('account_length_multi') ? document.getElementById('account_length_multi').value : 10;
            if (!raw || !bank) return alert('Vui lòng nhập danh sách tên và chọn ngân hàng');
            let names = raw.split('\n').map(x => normalizeName(x)).filter(x => x !== '');
            btnMulti.disabled = true;
            btnMulti.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo...';
            fetch('/va/create-va-multiple', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ names, bank, account_length: accountLength })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status == 'success') {
                        document.getElementById('va_names').value = '';
                        location.reload();
                    } else {
                        $('#result').html('<div style="color:red">✖ ' + data.message + ' </div>');
                    }
                })
                .finally(() => {
                    btnMulti.disabled = false;
                    btnMulti.innerHTML = 'Tạo nhiều tài khoản';
                });
        };
    }

    $('#saveProfBtn').on('click', function () {
        let btn = $(this);
        let id = $('#prof_id').val();
        let name = $('#prof_name').val();
        let bank = $('#prof_bank').val();
        let stk = $('#prof_stk').val();
        let qrFile = $('#prof_qr')[0].files[0];
        if (!name || !bank || !stk) return alert("Vui lòng nhập đủ thông tin!");
        let formData = new FormData();
        formData.append('_token', csrf);
        if (id) formData.append('id', id);
        formData.append('name', name);
        formData.append('bank', bank);
        formData.append('stk', stk);
        if (qrFile) formData.append('qr_code', qrFile);
        btn.prop('disabled', true);
        $.ajax({
            url: '/user/profile', type: 'POST', data: formData, processData: false, contentType: false,
            success: function (res) { alert(res.message); location.reload(); },
            complete: function () { btn.prop('disabled', false); }
        });
    });

    $('#drawBtn').on('click', function () {
        let bank_id = $('#wd_bank_id').val();
        if (!bank_id) return alert('Vui lòng chọn tài khoản nhận tiền!');
        Swal.fire({
            title: 'Xác Nhận Rút Tiền',
            text: 'Bạn có chắc chắn muốn rút TOÀN BỘ số dư hiện có?',
            showCancelButton: true,
            confirmButtonText: 'Rút Tiền Ngay',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('/user/withdraw', { _token: csrf, bank_id: bank_id }, function (res) {
                    if (res.status == 'success') {
                        location.reload();
                    } else { alert(res.message); }
                });
            }
        });
    });

    if ($('#tab-create').length) loadHistory();
    if ($('#tab-profile').length || $('#tab-withdraw').length) loadStats();
    showTermsIfNeeded();

    $(document).on('click', '#closeModal, #closeModalIcon', function () {
        $('#vaModal').fadeOut();
        location.reload();
    });

    $('#cancelEditBtn').on('click', resetProfForm);
});

document.addEventListener('contextmenu', e => e.preventDefault());
document.onkeydown = function (e) {
    if (e.keyCode == 123) return false;
    if (e.ctrlKey && e.shiftKey && (e.keyCode == 'I'.charCodeAt(0) || e.keyCode == 'J'.charCodeAt(0))) return false;
    if (e.ctrlKey && (e.keyCode == 'U'.charCodeAt(0) || e.keyCode == 'S'.charCodeAt(0))) return false;
};
