<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SystemStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/system-status', [SystemStatusController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/gettoken', [AuthController::class, 'getToken']);
Route::post('/gettoken/client', [AuthController::class, 'getClientToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
