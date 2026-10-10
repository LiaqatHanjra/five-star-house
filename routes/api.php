<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\BookingAdminController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ServiceController;
use App\Http\Controllers\Api\Admin\SiteImageController;
use App\Http\Controllers\Api\Admin\StudioAdminController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PublicContentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/home', [PublicContentController::class, 'home']);
Route::get('/services', [PublicContentController::class, 'services']);
Route::get('/services/{slug}', [PublicContentController::class, 'service']);
Route::get('/booking', [PublicContentController::class, 'booking']);
Route::get('/studio', [BookingController::class, 'studio']);
Route::get('/availability', [BookingController::class, 'availability']);
Route::post('/quotes', [BookingController::class, 'quote']);
Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings/{reference}', [BookingController::class, 'show']);
Route::post('/bookings/{reference}/pay', [BookingController::class, 'pay']);
Route::get('/bookings/{reference}/payment/verify', [BookingController::class, 'verifyPayment']);
Route::post('/stripe/webhook', [BookingController::class, 'stripeWebhook']);

Route::post('/admin/login', [AuthController::class, 'login']);

Route::get('run-migrations', function () {
    Artisan::call('migrate');
    return redirect()->back()->with('success', 'Migration completed successfully.');
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'show']);

    Route::get('/site-images', [SiteImageController::class, 'index']);
    Route::post('/site-images', [SiteImageController::class, 'store']);
    Route::post('/site-images/{siteImage}', [SiteImageController::class, 'update']);
    Route::delete('/site-images/{siteImage}', [SiteImageController::class, 'destroy']);

    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::post('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

    Route::get('/bookings', [BookingAdminController::class, 'index']);
    Route::post('/bookings/{booking}/status', [BookingAdminController::class, 'updateStatus']);
    Route::get('/payments', [StudioAdminController::class, 'payments']);
    Route::get('/customers', [StudioAdminController::class, 'customers']);
    Route::get('/settings', [StudioAdminController::class, 'settings']);
    Route::post('/settings', [StudioAdminController::class, 'updateSettings']);
});
