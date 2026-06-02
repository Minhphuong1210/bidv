<?php

namespace App\Modules\BIDV\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use App\Modules\BIDV\Helpers\BIDVCryptoHelper;

class BIDVClientService
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $certificate;
    protected $privateKey;
    protected $symmetricKeyHex;
    protected $merchantId;
    protected $serviceId;

    public function __construct()
    {
        $baseUrl = config('bidv.base_url');
        $port = config('bidv.port');
        $this->baseUrl = rtrim($baseUrl, '/');
        if (!empty($port)) {
            $this->baseUrl .= ':' . $port;
        }
        $this->clientId = config('bidv.client_id');
        $this->clientSecret = config('bidv.client_secret');

        $certificatePath = storage_path('key/certificate.pem');
        $privateKeyPath = storage_path('key/privateKey.key');

        if (!file_exists($certificatePath)) {
            \Illuminate\Support\Facades\Log::warning('Certificate file not found at ' . $certificatePath);
            return;
        }

        if (!file_exists($privateKeyPath)) {
            \Illuminate\Support\Facades\Log::warning('Private key file not found at ' . $privateKeyPath);
            return;
        }
        $certContent = file_get_contents($certificatePath);

        $certContent = str_replace([
            "-----BEGIN CERTIFICATE-----",
            "-----END CERTIFICATE-----",
            "\r",
            "\n"
        ], '', $certContent);

        $this->certificate = trim($certContent);



//        $privateKeyContent = file_get_contents($privateKeyPath);
//
//        $privateKeyContent = str_replace([
//            "-----BEGIN CERTIFICATE-----",
//            "-----END CERTIFICATE-----",
//            "\r",
//            "\n"
//        ], '', $certContent);
//
//        $this->privateKey = trim($privateKeyContent);



        $privateKeyContent = file_get_contents($privateKeyPath);

        $this->privateKey = trim($privateKeyContent);

        $this->symmetricKeyHex = config('bidv.symmetric_key_hex');
        $this->merchantId = config('bidv.merchant_id');
        $this->serviceId = config('bidv.service_id');
    }

    /**
     * Get Access Token (OAuth2)
     */
    public function getAccessToken()
    {
        $cacheKey = 'bidv_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $endpoint = $this->baseUrl . config('bidv.base_path') . '/openapi/oauth2/token';

        $client = new Client();
        try {
            $response = $client->post($endpoint, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'read' // Or any scope provided by BIDV
                ]
            ]);

            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);

            if (!is_array($data)) {
                throw new \Exception("Invalid JSON from BIDV Token API: " . $content);
            }

            if (!isset($data['access_token'])) {
                throw new \Exception("BIDV Token API Error: " . $content);
            }

            $token = $data['access_token'];

            Cache::put($cacheKey, $token, 3300);
            return $token;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            throw new \Exception("Failed to get BIDV Access Token: " . $errorBody);
        } catch (\Exception $e) {
            throw new \Exception("Failed to get BIDV Access Token: " . $e->getMessage());
        }
    }

    /**
     * Common API Caller
     */
    private function callApi($uriPath, array $body, $isJwe = true)
    {
        $token = $this->getAccessToken();


        $endpoint = $this->baseUrl . config('bidv.paygate_base_path') . $uriPath;

        // Interaction ID must be exactly 12 digits
        $interactionId = gmdate('ymdHis');

        $timestamp = gmdate('Y-m-d\TH:i:s.v\Z');

        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);

        \Log::info([
            'timestamp' => $timestamp,
            'server_time' => now()->toDateTimeString(),
            'timezone' => date_default_timezone_get(),
        ]);

        if ($isJwe) {

            $jwePayload = BIDVCryptoHelper::createJWE($jsonBody, $this->symmetricKeyHex);



            $jwsSignature = BIDVCryptoHelper::createDetachedJWS($jwePayload, $this->privateKey);
            $finalBody = $jwePayload;

        } else {

            $jwsSignature = BIDVCryptoHelper::createDetachedJWS($jsonBody, $this->privateKey);
            $finalBody = $jsonBody;
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Channel' => config('bidv.channel', 'Gia Long'),
            'User-Agent' => config('bidv.user_agent', 'KDBA Client'),
            'X-Client-Certificate' => str_replace(["\r", "\n"], "", $this->certificate),
            'X-API-Interaction-ID' => $interactionId,
            'Timestamp' => $timestamp,
            'Authorization' => 'Bearer ' . $token,
            'X-JWS-Signature' => $jwsSignature,
        ];



        $client = new Client();
        try {
            $response = $client->post($endpoint, [
                'headers' => $headers,
                'body' => $finalBody
            ]);

            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);

            if (!is_array($data)) {
                throw new \Exception("Invalid JSON from BIDV API: " . $content);
            }

            return $data;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            throw new \Exception("BIDV API Error: " . $errorBody);
        } catch (\Exception $e) {
            throw new \Exception("BIDV API Error: " . $e->getMessage());
        }
    }

    /**
     * API 1.3.1 Tạo/Sửa/Hủy TKDD
     * action: 1 (Tạo mới), 2 (Xóa), 3 (Sửa)
     */
    public function manageVirtualAccount($action, $payerList)
    {
        // Interaction ID must be exactly 12 digits
        $interactionId = date('ymd') . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $merchantName = config('bidv.merchant_name');
        $secretCode = config('bidv.secret_code');

        $dataToHash = $interactionId . '|' . $this->serviceId . '|' . $this->merchantId . '|' . $merchantName . '|' . $secretCode;
        $secureCode = BIDVCryptoHelper::getBase64SHA256($dataToHash);


        $body = [
            'secureCode' => $secureCode,
            'serviceId' => $this->serviceId,
            'merchantId' => $this->merchantId,
            'merchantName' => $merchantName,
            'action' => $action,
            'serviceCode' => config('bidv.service_code'),
            'payerList' => $payerList
        ];

        return $this->callApi('/virtualAccount/payer/v1', $body);
    }

    /**
     * Sanitize string: Remove Vietnamese accents and special characters
     */
    private function sanitizeString($string)
    {
        if (!$string) return $string;
        
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $string);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
        $str = preg_replace("/(đ)/", "d", $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", "A", $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", "E", $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", "I", $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", "O", $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", "U", $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", "Y", $str);
        $str = preg_replace("/(Đ)/", "D", $str);
        
        // Remove special characters: &,<,>,/,\,|…
        $str = preg_replace('/[&<>\/\\\\\|…]/u', ' ', $str);
        
        // Ensure no newlines or multiple spaces
        $str = trim(preg_replace('/\s+/', ' ', $str));
        
        return $str;
    }

    /**
     * API 1.3.2 Tạo mã VietQR TKDD
     */
    public function generateVietQR($code, $name, $amount = null, $description = null)
    {
        $body = [
            'serviceId' => $this->serviceId,
            'code' => $this->sanitizeString($code), // Ma KH, ma hoa don...
            'name' => $this->sanitizeString($name), // Ten chu TKDD
        ];

        if ($amount) {
            $body['amount'] = (string)$amount;
        }

        if ($description) {
            $body['description'] = $this->sanitizeString($description);
        }

        return $this->callApi('/virtualAccount/genVietQR/v1', $body);
    }

    /**
     * API 1.4 Đối soát BIDV
     * type: 1 (BIDV tra file GD thanh cong), 2 (NCC gui file doi soat chenh lech)
     * fileType: 1, 2, 3, 4, 5
     */
    public function reconciliation($type, $transDate, $fileType = '1', $fileContentBase64 = null)
    {
        $body = [
            'type' => (string)$type,
            'providerId' => $this->merchantId,
            'serviceId' => $this->serviceId,
            'transDate' => $transDate, // YYYYMMDD
            'fileType' => (string)$fileType
        ];

        if ($fileContentBase64) {
            $body['fileContent'] = $fileContentBase64;
        }

        return $this->callApi('/common/reconciliation/v1', $body);
    }
}

