<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

// ทดสอบการ update git //
Route::get('/', [ChatController::class, 'index'])->name('home');
Route::get('/layout', function () {
    return view('layout.layout');
});

// Dashboard route //
Route::get('/dashboard/home', [ChatController::class, 'index'])->name('dashboard.home');
Route::post('/chat', [ChatController::class, 'chat'])->name('chat'); // AJAX
Route::get('/emails', [ChatController::class, 'emails'])->name('emails.list'); // (ออปชัน)
Route::put('/emails/{id}', [ChatController::class, 'updateEmail'])->name('emails.update'); // อัปเดตอีเมล
Route::post('/emails/{id}/send', [ChatController::class, 'sendEmail'])->name('emails.send'); // ส่งอีเมล

// Debug routes
Route::get('/debug/customers', function () {
    $customers = \App\Models\Customer::all();
    return response()->json($customers);
});

Route::get('/debug/emails', function () {
    $emails = \App\Models\EmailData::with('contacts')->get();
    return response()->json($emails);
});

Route::get('/debug/chat-messages', function () {
    $messages = \App\Models\ChatMessage::orderBy('created_at', 'desc')->limit(20)->get();
    return response()->json($messages);
});

// Test Routes
Route::get('/test/basic', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'System is working!',
        'timestamp' => now(),
        'database' => [
            'emails' => \App\Models\EmailData::count(),
            'customers' => \App\Models\Customer::count(),
            'chat_messages' => \App\Models\ChatMessage::count()
        ]
    ]);
});

Route::get('/test/chat-memory', function () {
    try {
        $recentMessages = \App\Models\ChatMessage::orderBy('created_at', 'desc')->limit(10)->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Chat memory system working!',
            'recent_messages_count' => $recentMessages->count(),
            'messages' => $recentMessages->map(function($msg) {
                return [
                    'session_id' => $msg->session_id,
                    'role' => $msg->role,
                    'message' => substr($msg->message, 0, 50) . '...',
                    'created_at' => $msg->created_at
                ];
            })
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});

Route::get('/test/database-emails', function () {
    try {
        $emails = \App\Models\EmailData::with('contacts')->limit(5)->get();
        
        return response()->json([
            'status' => 'success',
            'count' => $emails->count(),
            'emails' => $emails->map(function($email) {
                return [
                    'id' => $email->id,
                    'subject' => $email->subject,
                    'body' => substr($email->body, 0, 100) . '...',
                    'contacts_count' => $email->contacts->count(),
                    'created_at' => $email->created_at
                ];
            })
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});