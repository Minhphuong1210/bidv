<?php

namespace App\Modules\BIDV\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\BIDV\Services\BIDVServerService;
use Illuminate\Support\Facades\Log;

class BIDVWebhookController extends Controller
{
    protected $bidvServerService;

    public function __construct(BIDVServerService $bidvServerService)
    {
        $this->bidvServerService = $bidvServerService;
    }

    /**
     * Webhook: Vấn tin hóa đơn
     */
    public function getBill(Request $request)
    {
        $payload = $request->all();

        // Tự động tạo checksum nếu dùng từ form test (không có checksum)
        if (!isset($payload['checksum'])) {
            $secretCode = config('bidv.secret_code');
            $dataToHash = $secretCode . '+' . ($payload['serviceId'] ?? '') . '+' . ($payload['customerId'] ?? '');
            $payload['checksum'] = \App\Modules\BIDV\Helpers\BIDVCryptoHelper::getBase64SHA256($dataToHash);
        }

        Log::info('BIDV getbill requested: ', $payload);

        try {
            $response = $this->bidvServerService->handleGetBill($payload);
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('BIDV getbill error: ' . $e->getMessage());
            return response()->json([
                'result_code' => '031',
                'result_desc' => 'Có lỗi phát sinh từ hệ thống'
            ]);
        }
    }

    /**
     * Webhook: Gạch nợ hóa đơn
     */
    public function payBill(Request $request)
    {
        $payload = $request->all();

        // Tự động tạo checksum nếu dùng từ form test (không có checksum)
        if (!isset($payload['checksum'])) {
            $secretCode = config('bidv.secret_code');
            $dataToHash = $secretCode . '+|' . ($payload['transId'] ?? '') . '+' . ($payload['amount'] ?? '');
            $payload['checksum'] = \App\Modules\BIDV\Helpers\BIDVCryptoHelper::getBase64SHA256($dataToHash);
        }

        Log::info('BIDV paybill requested: ', $payload);

        try {
            $response = $this->bidvServerService->handlePayBill($payload);
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('BIDV paybill error: ' . $e->getMessage());
            return response()->json([
                'result_code' => '031',
                'result_desc' => 'Có lỗi phát sinh từ hệ thống'
            ]);
        }
    }
}
