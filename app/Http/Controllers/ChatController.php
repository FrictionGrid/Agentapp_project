<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\ChatBot;
use App\Models\ChatMessage;

class ChatController extends Controller
{
    public function __construct(
        protected ChatBot $chatbot
    ) {}

    public function index()
    {
        return view('Dashboard');
    }

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'session_id' => ['nullable', 'string', 'max:255'],
        ]);

        $userInput = $data['message'];
        $sessionId = $data['session_id'] ?? $request->session()->getId();

        try {
            // บันทึกข้อความของผู้ใช้
            ChatMessage::saveMessage($sessionId, 'user', $userInput);

            // ดึงประวัติข้อความล่าสุด
            $recentMessages = ChatMessage::getRecentMessages($sessionId, 10);

            // ส่งข้อความไปยัง ChatBot พร้อมบริบท
            $reply = $this->chatbot->chatWithContext($userInput, $recentMessages);

            // บันทึกคำตอบของ AI
            ChatMessage::saveMessage($sessionId, 'assistant', $reply);

            return response()->json([
                'reply' => $reply
            ]);

        } catch (\Throwable $e) {
            Log::error('[ChatController] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'reply' => 'เกิดข้อผิดพลาดภายในระบบ โปรดลองใหม่อีกครั้ง'
            ], 500);
        }
    }
}





