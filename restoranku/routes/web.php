<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;

Route::get('/', function () {
    return redirect()->route('menu');
});

Route::get('/menu', [MenuController::class, 'index'])->name('menu');
Route::get('/cart', [MenuController::class, 'cart'])->name('cart');
Route::post('/add-to-cart', [MenuController::class, 'addToCart'])->name('add.to.cart');

Route::get('/checkout', function () {
    return view('customer.cart');
})->name('checkout');