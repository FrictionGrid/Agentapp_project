<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ChatAiService;
use App\Models\ChatState;
use App\Models\Customer;
use App\Models\Emaildata;

class ChatController extends Controller
{
    protected $chatAiService;

    public function __construct(ChatAiService $chatAiService)
    {
        $this->chatAiService = $chatAiService;
    }

    // แสดงหน้าเว็บแชท (GET)
    public function index()
    {
        return view('dashboard.email'); // เปลี่ยนเป็นชื่อ view ที่คุณใช้จริง
    }

    // รับข้อความจาก frontend ส่งให้ AI แล้วตอบกลับ (POST)
    public function chat(Request $request)
    {
        $userInput = $request->input('message');
        $aiResult = $this->chatAiService->Input_Text($userInput);

        return response()->json([
            'intent' => $aiResult['intent'] ?? 'chat',
            'reply' => $aiResult['reply'] ?? '',
        ]);
    }
}
