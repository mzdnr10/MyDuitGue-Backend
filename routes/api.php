<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\SystemStatusController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/system-status', [SystemStatusController::class, 'index']);
Route::get('/health', [SystemStatusController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/gettoken', [AuthController::class, 'getToken']);
Route::post('/gettoken/client', [AuthController::class, 'getClientToken']);
Route::post('/auth/google', [AuthController::class, 'google']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [AuthController::class, 'me']);
    Route::match(['put', 'patch'], '/profile', [AuthController::class, 'updateProfile']);
    Route::middleware('role:user,staff,admin')->group(function () {
        Route::apiResource('transactions', TransactionController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/statistics', [StatisticsController::class, 'index']);
        Route::get('/transactions/{transaction}/payment-proofs', [PaymentProofController::class, 'index']);
        Route::post('/transactions/{transaction}/payment-proofs', [PaymentProofController::class, 'store']);
    });
    Route::prefix('backoffice')->middleware('role:staff,admin')->group(function () {
        Route::get('/users', [BackofficeController::class, 'users']);
        Route::get('/transactions', [BackofficeController::class, 'transactions']);
        Route::get('/categories', [BackofficeController::class, 'categories']);
        Route::get('/dashboard', [BackofficeController::class, 'dashboard']);
    });
});
