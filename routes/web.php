<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/dashboard/home', [ChatController::class, 'index'])->name('dashboard.home');
Route::post('/chat', [ChatController::class, 'chat'])->name('chat'); 