<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;

final class ChatBot
{
    protected $client;
    protected string $model;

    public function __construct()
    {
        $apiKey = config('openai.api_key');
        $this->model = config('openai.model', 'gpt-4o-mini');
        $this->client = OpenAI::client($apiKey);
    }

    public function chat(string $message): string
    {
        return $this->chatWithContext($message, []);
    }

    public function chatWithContext(string $message, array $recentMessages = []): string
    {
        try {
            // สร้าง messages array เริ่มต้นด้วย system prompt
            $messages = [
                ['role' => 'system', 'content' => $this->systemPrompt()]
            ];

            // เพิ่มประวัติข้อความก่อนหน้า (ไม่เกิน 5 ข้อความ)
            if (!empty($recentMessages)) {
                $messages = array_merge($messages, $recentMessages);
                Log::info('[ChatBot] Added recent messages to context', [
                    'context_count' => count($recentMessages),
                    'total_messages' => count($messages)
                ]);
            } else {
                Log::info('[ChatBot] No recent messages found, using fresh context');
            }

            // เพิ่มข้อความปัจจุบันของผู้ใช้
            $messages[] = ['role' => 'user', 'content' => $message];

            Log::info('[ChatBot] Sending to OpenAI', [
                'model' => $this->model,
                'message_count' => count($messages)
            ]);

            $result = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            $content = $result->choices[0]->message->content ?? '';
            Log::info('[ChatBot] OpenAI response received', ['response_length' => strlen($content)]);
            
            return $content !== '' ? $content : 'ขอโทษครับ ผมยังตอบไม่ได้ ลองใหม่อีกครั้งได้ไหมครับ';
            
        } catch (\OpenAI\Exceptions\ErrorException $e) {
            Log::error('[ChatBot] OpenAI API Error: ' . $e->getMessage(), [
                'error_code' => $e->getCode(),
                'error_type' => get_class($e)
            ]);
            return 'ระบบ AI กำลังประสบปัญหา กรุณาลองใหม่ในอีกสักครู่';
        } catch (\Exception $e) {
            Log::error('[ChatBot] Network/Connection Error: ' . $e->getMessage(), [
                'error_code' => $e->getCode(),
                'error_type' => get_class($e)
            ]);
            return 'ไม่สามารถเชื่อมต่อกับ AI ได้ขณะนี้ กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต';
        } catch (\Throwable $e) {
            Log::error('[ChatBot] Unexpected Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 'เกิดข้อผิดพลาดที่ไม่คาดคิด กรุณาลองใหม่อีกครั้ง';
        }
    }

    protected function systemPrompt(): string
    {
        return <<<PROMPT
คุณคือผู้ช่วยสนทนาภาษาไทยที่เป็นมิตรและให้ความช่วยเหลือ

คำแนะนำในการตอบ:
- ตอบเป็นภาษาไทยเสมอ
- ให้คำตอบที่สั้น กระชับ และเป็นประโยชน์
- ใช้ภาษาสุภาพและเป็นกันเอง
- หากไม่ทราบคำตอบให้บอกตรงๆ ว่าไม่ทราบ
- ไม่ต้องแสดงความคิดเห็นทางการเมืองหรือเรื่องที่อาจก่อให้เกิดความขัดแย้ง

ตอบแค่เนื้อหาที่ถูกถาม ไม่ต้องอธิบายเพิ่มเติมหากไม่จำเป็น
PROMPT;
    }
}
