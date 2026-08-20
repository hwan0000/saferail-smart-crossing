<?php

use App\Http\Controllers\Api\NodeRedController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Node-RED Integration
|--------------------------------------------------------------------------
|
| Semua endpoint di bawah ini digunakan oleh Node-RED untuk mengirim
| data secara otomatis ke website Smart Railway Crossing.
|
| Base URL: http://your-domain.com/api
|
*/

Route::prefix('v1')->group(function () {

    // --- Sensor HC-SR04 ---
    // Node-RED POST ke: /api/v1/sensor
    Route::post('/sensor', [NodeRedController::class, 'storeSensor']);
    // Dashboard GET dari: /api/v1/sensor/latest
    Route::get('/sensor/latest', [NodeRedController::class, 'latestSensor']);

    // --- Device Status (Palang & Lampu) ---
    // Node-RED POST ke: /api/v1/device
    Route::post('/device', [NodeRedController::class, 'storeDevice']);
    // Dashboard GET dari: /api/v1/device/latest
    Route::get('/device/latest', [NodeRedController::class, 'latestDevices']);

    // --- AI Predictions ---
    // Node-RED POST ke: /api/v1/prediction
    Route::post('/prediction', [NodeRedController::class, 'storePrediction']);
    // Dashboard GET dari: /api/v1/prediction/latest
    Route::get('/prediction/latest', [NodeRedController::class, 'latestPrediction']);

    // --- SYNC (New) ---
    Route::post('/sync', [NodeRedController::class, 'storeSync']);
    Route::get('/dashboard/latest', [NodeRedController::class, 'latestDashboard']);

});
