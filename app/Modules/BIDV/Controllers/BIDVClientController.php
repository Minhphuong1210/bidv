<?php

namespace App\Modules\BIDV\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\BIDV\Services\BIDVClientService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\VaAccount;

class BIDVClientController extends Controller
{
    protected $bidvClientService;

    public function __construct(BIDVClientService $bidvClientService)
    {
        $this->bidvClientService = $bidvClientService;
    }

    /**
     * Test function: Generate QR Code
     */
    public function testGenerateQR(Request $request)
    {
        $name = $request->input('name', 'NGUYEN VAN A');
        $amount = $request->input('amount', 0);
        $description = $request->input('description', 'Tao Tai Khoan');

        $length = env('VA_ACCOUNT_LENGTH', 10);
        
        // Loop to ensure the generated code (account number) is unique
        do {
            $code = '';
            $code .= rand(1, 9);
            for ($i = 1; $i < $length; $i++) {
                $code .= rand(0, 9);
            }
        } while (VaAccount::where('va_number', $code)->exists());

        try {
            $response = $this->bidvClientService->generateVietQR($code, $name, $amount, $description);
            
            $qrCodeString = null;
            if (is_array($response)) {
                $qrCodeString = $response['qrCode'] ?? ($response['qrData'] ?? ($response['data']['qrCode'] ?? ($response['data']['qrData'] ?? null)));
            }

            $quickLink = null;
            if ($qrCodeString) {
                $quickLink = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrCodeString);
            }

            // Save the unique VA code to the database
            $va = VaAccount::create([
                'user_id' => auth()->id() ?? 1,
                'va_number' => $code,
                'merchant_name' => $name,
                'bank' => 'BIDV',
                'bank_full' => 'BIDV',
                'type' => 1,
                'amount' => $amount,
                'amount_int' => (int) $amount,
                'bill_count' => 0,
                'status' => 1,
                'created_date' => now(),
                'created_by' => auth()->id() ?? 1,
                'fee_rate' => env('DEFAULT_FEE_RATE', 8),
                'quick_link' => $quickLink,
            ]);

            $va->update([
                'ma_don_hang' => 'DH'
                    . now()->format('ymdHis')
                    . $va->id
                    . rand(100, 999)
            ]);

            return response()->json([
                'success' => true,
                'va_number' => $code,
                'merchant_name' => $name,
                'qr_data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show test webhook form
     */
    public function showTestWebhookForm()
    {
        return view('BIDV::test-webhook');
    }

    /**
     * Test Get Bill webhook by generating checksum and posting to the webhook
     */
    public function testWebhookGetBill(Request $request)
    {
        $payload = [
            'customerId' => $request->input('customerId'),
            'serviceId' => $request->input('serviceId'),
        ];

        $secretCode = config('bidv.secret_code');

        $dataToHash = $secretCode . '+' . $payload['serviceId'] . '+' . $payload['customerId'];

        $payload['checksum'] = \App\Modules\BIDV\Helpers\BIDVCryptoHelper::getBase64SHA256($dataToHash);

        $endpoint = url('api/bidv/getbill');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $finalBody = json_encode($payload);

        $client = new Client([
            'verify' => false
        ]);
        Log::info('BIDV TEST REQUEST', [
            'url'     => $endpoint,
            'headers' => $headers,
            'body'    => $payload,
        ]);
        try {

            $response = $client->post($endpoint, [
                'headers' => $headers,
                'body' => $finalBody
            ]);

            $content = $response->getBody()->getContents();

            $data = json_decode($content, true);

            if (!is_array($data)) {
                throw new \Exception("Invalid JSON response: " . $content);
            }

            return response()->json($data);

        } catch (RequestException $e) {

            $errorBody = $e->hasResponse()
                ? $e->getResponse()->getBody()->getContents()
                : $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => 'Request Error',
                'error' => $errorBody
            ], 500);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'System Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Pay Bill webhook by generating checksum and posting to the webhook
     */
    public function testWebhookPayBill(Request $request)
    {
        $payload = [
            'transId'    => $request->input('transId'),
            'transDate'  => $request->input('transDate'),
            'customerId' => $request->input('customerId'),
            'billId'     => $request->input('billId'),
            'amount'     => $request->input('amount'),
        ];

        $secretCode = config('bidv.secret_code');

        $dataToHash = $secretCode . '+|' . $payload['transId'] . '+' . $payload['amount'];

        $payload['checksum'] = \App\Modules\BIDV\Helpers\BIDVCryptoHelper::getBase64SHA256($dataToHash);

        $endpoint = url('api/bidv/paybill');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        $finalBody = json_encode($payload);

        $client = new Client([
            'verify' => false
        ]);

        // LOG REQUEST
        Log::info('BIDV PAYBILL REQUEST', [
            'url'     => $endpoint,
            'headers' => $headers,
            'body'    => $payload,
        ]);

        try {

            $response = $client->post($endpoint, [
                'headers' => $headers,
                'body'    => $finalBody
            ]);

            $content = $response->getBody()->getContents();

            // LOG RESPONSE
            Log::info('BIDV PAYBILL RESPONSE', [
                'status'  => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body'    => $content,
            ]);

            $data = json_decode($content, true);

            if (!is_array($data)) {

                Log::error('BIDV PAYBILL INVALID JSON', [
                    'response' => $content
                ]);

                throw new \Exception("Invalid JSON response: " . $content);
            }

            return response()->json($data);

        } catch (RequestException $e) {

            $errorBody = $e->hasResponse()
                ? $e->getResponse()->getBody()->getContents()
                : $e->getMessage();

            // LOG REQUEST ERROR
            Log::error('BIDV PAYBILL REQUEST ERROR', [
                'message' => $e->getMessage(),
                'error'   => $errorBody,
                'request_headers' => $headers,
                'request_body'    => $payload,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Request Error',
                'error'   => $errorBody
            ], 500);

        } catch (\Exception $e) {

            // LOG SYSTEM ERROR
            Log::error('BIDV PAYBILL SYSTEM ERROR', [
                'message' => $e->getMessage(),
                'request_headers' => $headers,
                'request_body'    => $payload,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'System Error',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
