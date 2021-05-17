<?php

use Illuminate\Support\Facades\Route;


Route::get('/', [App\Http\Controllers\Cover1::class, 'index'])->name('cover1');
Route::get('/cover', [App\Http\Controllers\Cover::class, 'index'])->name('cover');
