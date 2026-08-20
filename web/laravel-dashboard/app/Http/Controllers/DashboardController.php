<?php

namespace App\Http\Controllers;

use App\Models\AiPrediction;
use App\Models\DeviceStatus;
use App\Models\SensorReading;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $latestPrediction = AiPrediction::latest('id')->first();
        $latestSensor     = SensorReading::latest('id')->first();
        $barrierStatus    = DeviceStatus::where('device_name', 'barrier_gate')->latest('id')->first();
        $signalStatus     = DeviceStatus::where('device_name', 'traffic_signal')->latest('id')->first();

        return view('dashboard.index', compact(
            'latestPrediction',
            'latestSensor',
            'barrierStatus',
            'signalStatus'
        ));
    }

    public function sensors()
    {
        $latestSensor  = SensorReading::latest('id')->first();
        $recentReadings = SensorReading::latest('id')->limit(20)->get();
        $totalReadings = SensorReading::count();

        return view('dashboard.sensors', compact('latestSensor', 'recentReadings', 'totalReadings'));
    }

    public function analytics()
    {
        $latestPrediction  = AiPrediction::latest('id')->first();
        $latestSensor      = SensorReading::latest('id')->first();
        $predictionHistory = AiPrediction::latest('id')->limit(20)->get();

        return view('dashboard.analytics', compact(
            'latestPrediction',
            'latestSensor',
            'predictionHistory'
        ));
    }

    public function devices()
    {
        $barrierStatus = DeviceStatus::where('device_name', 'barrier_gate')->latest('id')->first();
        $signalStatus  = DeviceStatus::where('device_name', 'traffic_signal')->latest('id')->first();
        $deviceHistory = DeviceStatus::latest('id')->limit(20)->get();

        return view('dashboard.devices', compact('barrierStatus', 'signalStatus', 'deviceHistory'));
    }

    public function history()
    {
        $sensorHistory = SensorReading::latest('id')->limit(50)->get();
        $deviceHistory = DeviceStatus::latest('id')->limit(50)->get();
        $predictionHistory = AiPrediction::latest('id')->limit(50)->get();

        return view('dashboard.history', compact('sensorHistory', 'deviceHistory', 'predictionHistory'));
    }
}
