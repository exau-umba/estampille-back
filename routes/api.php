<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\QrBatchController;
use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\Api\VerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AdminAuthController::class, 'register']);
    Route::post('/auth/login', [AdminAuthController::class, 'login']);

    Route::middleware('admin.token')->group(function (): void {
        Route::get('/auth/me', [AdminAuthController::class, 'me']);
        Route::post('/auth/logout', [AdminAuthController::class, 'logout']);

        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('certificates', CertificateController::class);

        Route::get('/qr-batches', [QrBatchController::class, 'index']);
        Route::post('/qr-batches', [QrBatchController::class, 'store']);
        Route::get('/qr-batches/{batch}', [QrBatchController::class, 'show']);
        Route::put('/qr-batches/{batch}', [QrBatchController::class, 'update']);
        Route::delete('/qr-batches/{batch}', [QrBatchController::class, 'destroy']);
        Route::get('/qr-batches/{batch}/codes', [QrBatchController::class, 'codes']);
        Route::patch('/qr-codes/{qrCode}/revoke', [QrCodeController::class, 'revoke']);
    });

    Route::get('/verify/{token}', [VerificationController::class, 'show'])->where('token', '.*');
});
