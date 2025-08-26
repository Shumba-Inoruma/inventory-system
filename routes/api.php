<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UsersController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);       // List all products
    Route::get('{id}', [ProductController::class, 'show']);    // Show single product
    Route::post('/', [ProductController::class, 'store']);     // Create product
    Route::put('{id}', [ProductController::class, 'update']);  // Update product
    Route::delete('{id}', [ProductController::class, 'destroy']); // Delete product
    Route::get('{id}/stock-count', [ProductController::class, 'stockCount']);

});



Route::prefix('stocks')->group(function () {
    Route::post('/add', [StockController::class, 'addStock']);     // Stock in
    Route::post('/remove', [StockController::class, 'removeStock']); // Stock out
});


Route::prefix('users')->group(function () {
    Route::post('/register', [UsersController::class, 'register']);
    Route::post('/activate', [UsersController::class, 'activate']);
    Route::post('/login', [UsersController::class, 'login']);
    Route::post('/logout', [UsersController::class, 'logout'])->middleware('auth:sanctum'); // protected
});


