<?php

use App\Http\Controllers\FirstController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\AuthController; 
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;  
use Illuminate\Http\Request;

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

Route::get('/login', [AuthController::class, 'show_login'])->name('login_show')->middleware('guest');

Route::post('/login', [AuthController::class, 'login_auth'])->name('login_auth');
Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:admin,owner'])->group(function () {
     Route::get('/products/insert-form', [StoreController::class, 'product_insert_form'])->name('products_insert_from'); 
     Route::post('/products/insert', [StoreController::class, 'insert_product'])->name('insert_product');
     Route::get('/products/edit/{product_id}', [StoreController::class, 'product_edit_form'])->name('products_edit_from');
     Route::put('/products/edit/{product_id}', [StoreController::class, 'update_product'])->name('update_product');
     Route::delete('/products/delete/{product_id}', [StoreController::class, 'delete_product'])->name('delete_product');
    });
    Route::middleware(['role:customer,admin,owner'])->group(function () {
     Route::get('/store', [StoreController::class, 'show'])->name('store'); 
    });
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
