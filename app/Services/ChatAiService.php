<?php

namespace App\Services;

use OpenAI; // ดึง openai libray // 

class ChatAiService 
{
    protected $client; // ตัวเเปรเก็บเอาไว้ติดต่อกับ openai api //

    public function __construct()
    {
        $this->client = OpenAI::client(env('OPENAI_API_KEY')); // สร้างตัวเเปรขึ้นมาโดยฟังชั่น OpenAI::client(...) สำหรับเเปลภาษาเเล้วเก็บไว้ที่ตัวเเปร $client
    }
    public function Input_Text($text)
    {
        $prompt = $this->buildPrompt($text); // สร้าง prompt สำหรับ AI (เพื่อแยก intent) // 
        $result = $this->client->chat()->create([ // ส่งข้อความไปยัง OpenAI (chat/completion) //
            'model' => 'gpt-4.1-nano-2025-04-14',
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt()],
                ['role' => 'user', 'content' => $prompt],
            ],
            'n' => 1, // จำนวนคำตอบที่ส่งมา เลือกหนึ่งเพื่อลดค่าใช้จ่าย //
        ]);
        $content = $result->choices[0]->message->content; // ต้องดูรูปเเบบ json ที่ส่งกลับมาจะเข้าใจ //
        $data = $this->parseAIResponse($content); // แปลงผลลัพธ์ให้เป็น array ที่ service //
        return $data;
    }

    protected function buildPrompt($userInput)
    {
        // สามารถเขียนเพิ่ม เพื่อให้ AI ทำงานได้ดีขึ้น
        // เช่น ถ้าต้องการให้ AI รู้ว่าผู้ใช้เป็นใคร หรือมีข้อมูลอะไรเพิ่มเติม
        // $context = "คุณคือผู้ช่วยส่วนตัวที่ช่วยตอบคำถามทางธุรกิจ: ";
        // $instruction = "ตอบคำถามอย่างชัดเจนและกระชับ: ";
        // return $context . $instruction . $userInput;
        return $userInput;
    }

    protected function getSystemPrompt()// ทำให้ AI เข้าใจบทบาทของตัวเอง หรือ เชียนให้ส่ง json เเบบไหนได้ //
    {
        return <<< PROMPT
    คุณคือ AI Agent ที่สามารถทั้งคุยกับผู้ใช้ และรับคำสั่งเพื่อดำเนินการ action ต่างๆ ได้
    ถ้าข้อความจากผู้ใช้เป็นคำสั่ง ให้ตอบกลับเป็น JSON เช่น:
    {"intent":"send_email", "params":{"to":"ชื่อผู้รับ", "message":"เนื้อความ"}, "reply":"กำลังส่งอีเมลให้ ..."}
    ถ้าเป็นแค่แชทธรรมดา ให้ตอบเป็น:
    {"intent":"chat", "reply":"..."}
    ห้ามเดา ถ้าไม่แน่ใจให้ตอบ intent เป็น "chat"
    PROMPT;
    }

    protected function parseAIResponse($content) // ป้องกัน erro เวลาส่งรูป json กลับมาไม่ตาม format //
    {
        $data = json_decode($content, true);
        if (!$data) {
            $data = [
                'intent' => 'chat',
                'reply' => $content,
            ]; 
        }
        return $data;
    }
}
