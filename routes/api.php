
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\AuthController;

// CRUD buku
Route::apiResource('books', BookController::class);

// Login API: tidak memerlukan token
Route::post('/login', [AuthController::class, 'login']);

// Endpoint yang memerlukan autentikasi Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Mendapatkan informasi pengguna yang sedang login
    Route::get('/user', function (Request $request) {
        return response()->json($request->user(), 200);
    });

    // Logout dan menghapus token aktif
    Route::post('/logout', [AuthController::class, 'logout']);
});
