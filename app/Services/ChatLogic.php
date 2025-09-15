<?php

namespace App\Services;

final class ChatLogic
{
    public function detect(string $userInput): array
    {
        $message = trim($userInput);
        
        // ตรวจสอบคำสั่ง agent
        if ($this->isAgentCommand($message)) {
            return $this->parseAgentCommand($message);
        }
        
        // ถ้าไม่ใช่คำสั่ง agent ให้ถือว่าเป็น chat ธรรมดา
        return [
            'intent' => 'chat',
            'params' => []
        ];
    }
    
    protected function isAgentCommand(string $message): bool
    {
        // ตรวจสอบคำสั่งที่ขึ้นต้นด้วย "agent" หรือ "เอเจนต์"
        $patterns = [
            '/^agent[\s:]/i',
            '/^เอเจนต์[\s:]/i',
            '/^agent$/i',
            '/^เอเจนต์$/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function parseAgentCommand(string $message): array
    {
        // แยกคำสั่ง agent ออกมา
        $message = preg_replace('/^(agent|เอเจนต์)[\s:]*/i', '', $message);
        $message = trim($message);
        
        // ตรวจสอบประเภทของ agent
        if ($this->isEmailAgent($message)) {
            return [
                'intent' => 'agent_email',
                'params' => [
                    'command' => $message
                ]
            ];
        }
        
        // ถ้าไม่ระบุประเภท agent ชัดเจน
        return [
            'intent' => 'agent_unknown',
            'params' => [
                'command' => $message
            ]
        ];
    }
    
    protected function isEmailAgent(string $message): bool
    {
        $emailKeywords = [
            'email', 'เมล', 'อีเมล', 'mail',
            'ส่ง', 'send', 'draft', 'ดราฟต์',
            'เขียน', 'สร้าง', 'create', 'write'
        ];
        
        $message = strtolower($message);
        
        foreach ($emailKeywords as $keyword) {
            if (strpos($message, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
