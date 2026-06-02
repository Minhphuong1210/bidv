<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'bidv', 'namespace' => 'App\Modules\BIDV\Controllers'], function () {
    // Testing endpoints for the Client Service
    Route::post('test-qr', 'BIDVClientController@testGenerateQR');
    
    // Testing endpoints for Webhooks
    Route::get('test-webhook', 'BIDVClientController@showTestWebhookForm');
    Route::post('getbill', 'BIDVWebhookController@getBill');
    Route::post('paybill', 'BIDVWebhookController@payBill');
});

Route::group(['prefix' => 'api/bidv', 'namespace' => 'App\Modules\BIDV\Controllers'], function () {
    // Webhook endpoints for BIDV to call (Server Service)
    Route::post('getbill', 'BIDVWebhookController@getBill');
    Route::post('paybill', 'BIDVWebhookController@payBill');
});
