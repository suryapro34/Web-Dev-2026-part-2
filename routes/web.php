<?php

use App\Http\Controllers\FirstController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StoreController; // ← ADD THIS
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hi', function(){
    return 'hi';
})->name('hi');

Route::get('/helo', function(){
    return 'helooo isb';
})->name('helo');

Route::get('/home', [HomeController::class, 'show'])->name('home');

Route::get('/home/sum', [FirstController::class, 'sum'])->name('home.sum');

Route::get('/home/multiply/{param1}/{param2?}', [FirstController::class, 'multiply'])->name('home.multiply');

Route::get('/about', function(){
    return view('about');
})->name('about');

Route::get('/store', [StoreController::class, 'show'])->name('store'); // ← FIXED

Route::get('/products/insert', [StoreController::class, 'product_insert_form'])->name('products_insert_from'); 

Route::post('/products/insert', [StoreController::class, 'insert_product'])->name('insert_product');

Route::get('/products/edit/{product_id}', [StoreController::class, 'product_edit_form'])->name('products_edit_from');

Route::put('/products/edit/{product_id}', [StoreController::class, 'update_product'])->name('update_product');

Route::delete('/products/delete/{product_id}', [StoreController::class, 'delete_product'])->name('delete_product');