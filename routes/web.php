<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\EmailController;

Route::get('/layout', function () {
    return view('layout.layout');
});

// Dashboard route //
Route::get('/dashboard/home', function () {
    return view('dashboard.email');
});

Route::get('/chat', [ChatController::class, 'index']);    // หน้าแชท
Route::post('/chat', [ChatController::class, 'chat']);    // API รับข้อความส่ง AI


// *** เพิ่ม Email API ***
Route::get('/emails', [EmailController::class, 'index']);
Route::get('/emails/{id}', [EmailController::class, 'show']);
Route::put('/emails/{id}', [EmailController::class, 'update']);
Route::post('/emails/{id}/confirm', [EmailController::class, 'confirm']);