<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\DashboardController;

Route::get('/', [LandingController::class, 'index']);
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/sensors', [DashboardController::class, 'sensors'])->name('dashboard.sensors');
Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
Route::get('/dashboard/devices', [DashboardController::class, 'devices'])->name('dashboard.devices');
Route::get('/dashboard/history', [DashboardController::class, 'history'])->name('dashboard.history');
