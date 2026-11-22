<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\SystemStatusController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Route::get('/debug', function () {
//     return response()->json([
//         'database' => DB::select('SELECT DATABASE() AS db')[0]->db,
//         'users_count' => DB::table('users')->count(),
//         'latest_user' => DB::table('users')->latest('id')->first(),
//     ]);
// });

Route::get('/system-status', [SystemStatusController::class, 'index']);
Route::get('/health', [SystemStatusController::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/gettoken', [AuthController::class, 'getToken']);
Route::post('/gettoken/client', [AuthController::class, 'getClientToken']);
Route::post('/auth/google', [AuthController::class, 'google']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/me', [AuthController::class, 'me']);
    Route::get('/profile', [AuthController::class, 'me']);
    Route::match(['put', 'patch'], '/profile', [AuthController::class, 'updateProfile']);
    Route::middleware('role:user')->group(function () {
        Route::post('/home', [HomeController::class, 'index']);
        Route::post('/home/weekly_expense', [HomeController::class, 'weekly_expense']);
        Route::post('/service', [ServicesController::class, 'index']);
        Route::post('/services/category-account', [ServicesController::class, 'categoryAndAccount']);
        Route::post('/transaction/store', [TransactionController::class, 'store']);

    });
});
