<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIDV Webhook Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .card { margin-bottom: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .response-box { background: #2d2d2d; color: #a9b7c6; padding: 15px; border-radius: 5px; min-height: 100px; max-height: 400px; overflow-y: auto; font-family: monospace; white-space: pre-wrap; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="mb-4 text-center">BIDV Webhook Testing Tool</h2>

    <div class="row">
        <!-- Get Bill Form -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Test GetBill (Vấn tin hóa đơn)</h5>
                </div>
                <div class="card-body">
                    <form id="getBillForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Service ID (Mã dịch vụ)</label>
                            <input type="text" class="form-control" id="serviceId" name="serviceId" value="7300000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Customer ID (Mã khách hàng)</label>
                            <input type="text" class="form-control" id="customerId" name="customerId" required placeholder="Nhập mã khách hàng/mã đơn hàng">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Gửi Yêu Cầu GetBill</button>
                    </form>
                    <div class="mt-3">
                        <label class="form-label">Response:</label>
                        <div class="response-box" id="getBillResponse"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pay Bill Form -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Test PayBill (Gạch nợ hóa đơn)</h5>
                </div>
                <div class="card-body">
                    <form id="payBillForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Transaction ID (Mã giao dịch NH)</label>
                            <input type="text" class="form-control" id="transId" name="transId" value="FT{{ time() }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Date (YYYYMMDDHHmmss)</label>
                            <input type="text" class="form-control" id="transDate" name="transDate" value="{{ date('YmdHis') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Customer ID (Mã khách hàng)</label>
                            <input type="text" class="form-control" id="payCustomerId" name="customerId" required placeholder="Nhập mã khách hàng">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bill ID (Mã hóa đơn)</label>
                            <input type="text" class="form-control" id="billId" name="billId" required placeholder="Nhập mã hóa đơn">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (Số tiền)</label>
                            <input type="number" class="form-control" id="amount" name="amount" required placeholder="Ví dụ: 100000">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Gửi Yêu Cầu PayBill</button>
                    </form>
                    <div class="mt-3">
                        <label class="form-label">Response:</label>
                        <div class="response-box" id="payBillResponse"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#getBillForm').on('submit', function(e) {
            e.preventDefault();
            $('#getBillResponse').text('Loading...');
            $.ajax({
                url: '{{ url("bidv/getbill") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#getBillResponse').text(JSON.stringify(response, null, 4));
                },
                error: function(xhr) {
                    $('#getBillResponse').text('Error:\n' + JSON.stringify(xhr.responseJSON, null, 4));
                }
            });
        });

        $('#payBillForm').on('submit', function(e) {
            e.preventDefault();
            $('#payBillResponse').text('Loading...');
            $.ajax({
                url: '{{ url("bidv/paybill") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#payBillResponse').text(JSON.stringify(response, null, 4));
                },
                error: function(xhr) {
                    $('#payBillResponse').text('Error:\n' + JSON.stringify(xhr.responseJSON, null, 4));
                }
            });
        });
    });
</script>

</body>
</html>
