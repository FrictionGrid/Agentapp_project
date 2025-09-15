<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\ChatLogic;
use App\Services\ChatBot;
use App\Services\AgentEmail;
use App\Models\EmailData;
use App\Models\ChatMessage;

class ChatController extends Controller
{
    public function __construct(
        protected ChatLogic $logic,
        protected ChatBot $chatbot,
        protected AgentEmail $agentEmail
    ) {}

    public function index()
    {
        $initialDrafts = $this->fetchDraftsPayload(20);
        return view('dashboard.email', ['initialDrafts' => $initialDrafts]);
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
            // ใช้ ChatLogic เพื่อแยกประเภทข้อความ
            $parsed = $this->logic->detect($userInput);
            $intent = $parsed['intent'] ?? 'chat';
            $command = $parsed['params']['command'] ?? null;

            // หากเป็น chat ธรรมดา
            if ($intent === 'chat') {
                Log::info('[ChatController] Chat mode detected', [
                    'session_id' => $sessionId,
                    'user_input' => $userInput
                ]);
                
                // บันทึกข้อความของผู้ใช้
                ChatMessage::saveMessage($sessionId, 'user', $userInput);
                Log::info('[ChatController] User message saved');
                
                // ดึงประวัติ 5 ข้อความล่าสุด (ไม่รวมข้อความที่พึ่งบันทึก)
                $recentMessages = ChatMessage::getRecentMessages($sessionId, 10);
                Log::info('[ChatController] Recent messages retrieved', [
                    'count' => count($recentMessages),
                    'messages' => $recentMessages
                ]);
                
                // ส่งข้อความพร้อมบริบทไป ChatBot
                $reply = $this->chatbot->chatWithContext($userInput, $recentMessages);
                Log::info('[ChatController] ChatBot reply received', ['reply' => $reply]);
                
                // บันทึกคำตอบของ AI
                ChatMessage::saveMessage($sessionId, 'assistant', $reply);
                Log::info('[ChatController] Assistant message saved');
                
                return response()->json([
                    'intent' => 'chat',
                    'reply' => $reply,
                    'drafts' => []
                ]);
            }

            // หากเป็น agent email mode
            if ($intent === 'agent_email') {
                // บันทึกข้อความของผู้ใช้
                ChatMessage::saveMessage($sessionId, 'user', $userInput);
                
                $result = $this->agentEmail->handle(['command' => $command]);
                
                // ดึงดราฟต์อีเมลล่าสุด
                $drafts = $this->fetchDraftsPayload(20);
                
                $replyMessage = $result['message'] ?? 'สร้างดราฟต์อีเมลเรียบร้อยแล้ว';
                
                // บันทึกคำตอบของ AI
                ChatMessage::saveMessage($sessionId, 'assistant', $replyMessage);
                
                return response()->json([
                    'intent' => 'agent_email',
                    'reply' => $replyMessage,
                    'action_status' => $result['status'] ?? 'success',
                    'drafts' => $drafts
                ]);
            }

            // หากเป็น agent mode ที่ไม่รู้จัก
            if ($intent === 'agent_unknown') {
                return response()->json([
                    'intent' => 'agent_unknown',
                    'reply' => 'โปรดระบุประเภท agent ที่ต้องการ เช่น "agent email สร้างอีเมลโปรโมชั่น"',
                    'action_status' => 'need_more_info'
                ], 400);
            }

            // กรณีอื่นๆ
            return response()->json([
                'intent' => 'unknown',
                'reply' => 'ไม่เข้าใจคำสั่ง โปรดลองใหม่อีกครั้ง',
                'action_status' => 'error'
            ], 400);

        } catch (\Throwable $e) {
            Log::error('[ChatController@chat] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $userInput
            ]);
            
            return response()->json([
                'intent' => 'error',
                'reply' => 'เกิดข้อผิดพลาดภายในระบบ โปรดลองใหม่อีกครั้ง',
                'action_status' => 'error'
            ], 500);
        }
    }

    public function emails(): JsonResponse
    {
        try {
            $drafts = $this->fetchDraftsPayload(50);
            return response()->json($drafts);
        } catch (\Throwable $e) {
            Log::error('[ChatController@emails] Error: ' . $e->getMessage());
            return response()->json(['error' => 'ไม่สามารถดึงข้อมูลอีเมลได้'], 500);
        }
    }


    public function updateEmail(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'subject' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
            ]);

            $email = EmailData::findOrFail($id);
            $email->update([
                'subject' => $data['subject'],
                'body' => $data['body'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'อัปเดตอีเมลเรียบร้อยแล้ว',
                'email' => [
                    'id' => $email->id,
                    'subject' => $email->subject,
                    'body' => $email->body,
                    'updated_at' => $email->updated_at?->toDateTimeString(),
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController@updateEmail] Error: ' . $e->getMessage(), [
                'id' => $id,
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตอีเมล'
            ], 500);
        }
    }

    public function sendEmail(Request $request, int $id): JsonResponse
    {
        try {
            $email = EmailData::with('contacts')->findOrFail($id);
            
            if ($email->contacts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีผู้รับอีเมล กรุณาเพิ่มผู้รับก่อนส่ง'
                ], 400);
            }

            if (empty($email->subject) || empty($email->body)) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณากรอกหัวข้อและเนื้อหาอีเมลให้ครบถ้วน'
                ], 400);
            }

            $recipientEmails = $email->contacts->pluck('email')->toArray();
            
            \Mail::raw($email->body, function ($message) use ($email, $recipientEmails) {
                $message->to($recipientEmails)
                        ->subject($email->subject);
            });

            return response()->json([
                'success' => true,
                'message' => 'ส่งอีเมลเรียบร้อยแล้ว ส่งไปยัง ' . count($recipientEmails) . ' ผู้รับ',
                'recipients_count' => count($recipientEmails),
                'recipients' => $recipientEmails
            ]);

        } catch (\Throwable $e) {
            Log::error('[ChatController@sendEmail] Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการส่งอีเมล: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function fetchDraftsPayload(int $limit = 50): array
    {
        $emails = EmailData::with('contacts')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $emails->map(function ($email) {
            return [
                'id' => $email->id,
                'subject' => $email->subject,
                'body' => $email->body,
                'contacts' => $email->contacts->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'email' => $contact->email,
                        'first_name' => $contact->first_name,
                        'last_name' => $contact->last_name,
                        'company' => $contact->company_name,
                        'group' => $contact->group,
                    ];
                })->toArray(),
                'created_at' => $email->created_at?->toDateTimeString(),
            ];
        })->toArray();
    }

}
