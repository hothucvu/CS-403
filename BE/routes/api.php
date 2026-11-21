<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Ví dụ route chỉ dành cho admin (sẽ dùng ở việc #22)
    Route::middleware('admin')->get('/admin/ping', function () {
        return response()->json(['success' => true, 'message' => 'Xin chào admin']);
    });
});