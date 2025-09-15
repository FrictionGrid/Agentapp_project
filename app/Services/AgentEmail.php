<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;
use App\Models\EmailData;
use App\Models\Customer;

final class AgentEmail
{
    protected $client;
    protected string $model;

    public function __construct()
    {
        $apiKey = config('openai.api_key');
        $this->model = config('openai.model', 'gpt-4o-mini');
        $this->client = OpenAI::client($apiKey);
    }

    public function handle(array $request): array
    {
        try {
            $command = $request['command'] ?? '';
            
            // ดึงข้อมูลลูกค้าจากฐานข้อมูล
            $customers = $this->getCustomersData();
            
            // สร้างอีเมลด้วย AI
            $emailData = $this->generateEmail($command, $customers);
            
            if ($emailData) {
                // บันทึกดราฟต์อีเมลลงฐานข้อมูล
                $savedEmail = $this->saveEmailDraft($emailData);
                
                return [
                    'status' => 'success',
                    'message' => 'สร้างดราฟต์อีเมลเรียบร้อยแล้ว',
                    'email_id' => $savedEmail->id ?? null
                ];
            }
            
            return [
                'status' => 'error',
                'message' => 'ไม่สามารถสร้างอีเมลได้'
            ];
            
        } catch (\Throwable $e) {
            Log::error('[AgentEmail] Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการสร้างอีเมล'
            ];
        }
    }

    protected function getCustomersData(): array
    {
        return Customer::limit(10)->get()->map(function ($customer) {
            return [
                'email' => $customer->email,
                'name' => trim($customer->first_name . ' ' . $customer->last_name),
                'company' => $customer->company_name,
                'group' => $customer->group
            ];
        })->toArray();
    }

    protected function generateEmail(string $command, array $customers): ?array
    {
        try {
            $customerInfo = $this->formatCustomerInfo($customers);
            
            $result = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $this->getEmailSystemPrompt()],
                    ['role' => 'user', 'content' => "คำสั่ง: {$command}\n\nข้อมูลลูกค้า:\n{$customerInfo}"],
                ],
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ]);

            $content = $result->choices[0]->message->content ?? '';
            $emailData = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($emailData)) {
                return $emailData;
            }
            
            return null;
            
        } catch (\Throwable $e) {
            Log::error('[AgentEmail] Generate email error: ' . $e->getMessage());
            return null;
        }
    }

    protected function formatCustomerInfo(array $customers): string
    {
        if (empty($customers)) {
            return "ไม่มีข้อมูลลูกค้าในระบบ";
        }

        $formatted = "";
        foreach ($customers as $index => $customer) {
            $formatted .= ($index + 1) . ". {$customer['name']} ({$customer['email']})";
            if (!empty($customer['company'])) {
                $formatted .= " - {$customer['company']}";
            }
            if (!empty($customer['group'])) {
                $formatted .= " [กลุ่ม: {$customer['group']}]";
            }
            $formatted .= "\n";
        }
        
        return $formatted;
    }

    protected function saveEmailDraft(array $emailData): ?EmailData
    {
        try {
            Log::info('[AgentEmail] Saving email data:', $emailData);
            
            $email = EmailData::create([
                'subject' => $emailData['subject'] ?? 'ไม่มีหัวข้อ',
                'body' => $emailData['body'] ?? '',
            ]);

            Log::info('[AgentEmail] Email created with ID:', ['id' => $email->id]);

            // เชื่อมโยงกับลูกค้าถ้ามี
            if (isset($emailData['recipients']) && is_array($emailData['recipients'])) {
                $customerIds = [];
                foreach ($emailData['recipients'] as $recipient) {
                    $customer = Customer::where('email', $recipient)->first();
                    if ($customer) {
                        $customerIds[] = $customer->id;
                        Log::info('[AgentEmail] Found customer:', ['email' => $recipient, 'id' => $customer->id]);
                    } else {
                        Log::info('[AgentEmail] Customer not found for email:', ['email' => $recipient]);
                    }
                }
                
                if (!empty($customerIds)) {
                    $email->contacts()->attach($customerIds);
                    Log::info('[AgentEmail] Attached customers:', $customerIds);
                } else {
                    Log::info('[AgentEmail] No customers to attach');
                }
            } else {
                Log::info('[AgentEmail] No recipients in email data');
            }

            return $email;
            
        } catch (\Throwable $e) {
            Log::error('[AgentEmail] Save draft error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'emailData' => $emailData
            ]);
            return null;
        }
    }

    protected function getEmailSystemPrompt(): string
    {
        return <<<PROMPT
คุณคือผู้ช่วยสร้างอีเมลที่ชำนาญในการเขียนอีเมลธุรกิจภาษาไทย

งานของคุณ:
1. วิเคราะห์คำสั่งที่ได้รับ
2. เลือกลูกค้าที่เหมาะสมจากรายชื่อที่ให้มา (ถ้ามี)
3. สร้างอีเมลที่เหมาะสมตามคำสั่ง

รูปแบบการตอบ (JSON เท่านั้น):
{
  "subject": "หัวข้ออีเมล",
  "body": "เนื้อหาอีเมลที่สมบูรณ์",
  "recipients": ["email1@example.com", "email2@example.com"]
}

หลักการเขียนอีเมล:
- ใช้ภาษาไทยที่สุภาพและเป็นทางการ
- เนื้อหากระชับ ชัดเจน 
- มีการทักทายและปิดท้ายที่เหมาะสม
- ถ้าไม่มีลูกค้าเฉพาะเจาะจง ให้ recipients เป็น array ว่าง []

ตัวอย่าง:
- "ส่งเมลหาลูกค้าเรื่องสินค้าใหม่" → สร้างอีเมลแนะนำสินค้าใหม่
- "แจ้งข่าวโปรโมชั่น" → สร้างอีเมลโปรโมชั่น
- "ขอบคุณลูกค้า" → สร้างอีเมลขอบคุณ
PROMPT;
    }
}
