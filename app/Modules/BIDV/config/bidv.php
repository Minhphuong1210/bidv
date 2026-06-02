<?php

return [
    'base_url' => env('BIDV_BASE_URL', 'https://bidv.net'),
    'port' => env('BIDV_PORT', '9303'),
    'base_path' => env('BIDV_BASE_PATH', '/bidvorg/service'),
    'paygate_base_path' => env('BIDV_PAYGATE_BASE_PATH', '/bidvorg/service/open-banking/paygate'),
    
    'client_id' => env('BIDV_CLIENT_ID', '47390bb6ca873e07275478fdcb71c170'),
    'client_secret' => env('BIDV_CLIENT_SECRET', '11c8aeb7e413edd4c94c5a244376acc7'),
    
    'merchant_id' => env('BIDV_MERCHANT_ID', ''),
    'merchant_name' => env('BIDV_MERCHANT_NAME', ''),
    'service_id' => env('BIDV_SERVICE_ID', ''),
    'service_code' => env('BIDV_SERVICE_CODE', ''),
    'secret_code' => env('BIDV_SECRET_CODE', ''),
    

    'certificate' => env('BIDV_CERTIFICATE', ''),

    'private_key' => env('BIDV_PRIVATE_KEY', ''),
    
    // Symmetric Key 256-bit (Hex string) dùng để mã hoá JWE Payload
    'symmetric_key_hex' => env('BIDV_SYMMETRIC_KEY_HEX', ''),
];
