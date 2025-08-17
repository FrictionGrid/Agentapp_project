<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Emaildata;

class ActionAIService
{
    /**
     * ทำ action จริงตาม intent และ params ที่ได้จาก AI
     */
    public function handleAction($intent, $params)
    {
        switch ($intent) {
            case 'send_email':
                return $this->sendEmail($params);
            default:
                return [
                    'status' => 'error',
                    'message' => 'ไม่รู้จักคำสั่งนี้'
                ];
        }
    }

    protected function sendEmail($params)
    {
        $to = $params['to'] ?? null;
        $subject = $params['subject'] ?? '(no subject)';
        $body = $params['message'] ?? '';

        // 1. ค้นหา contact ในฐานข้อมูล (ตาม email, ชื่อ, นามสกุล)
        $contact = Customer::where('email', $to)
            ->orWhere('first_name', $to)
            ->orWhere('last_name', $to)
            ->first();

        if (!$contact) {
            return [
                'status' => 'error',
                'message' => 'ไม่พบผู้ติดต่อ (' . $to . ')'
            ];
        }

        // 2. สร้าง email ใหม่ใน emaildatas
        $email = Emaildata::create([
            'subject' => $subject,
            'body' => $body,
        ]);

        // 3. ผูกความสัมพันธ์ผ่าน pivot
        $contact->emails()->attach($email->id, [
            'status' => 'pending' // หรือกำหนดตาม logic
        ]);

        return [
            'status' => 'success',
            'message' => "สร้างอีเมลสำเร็จให้ {$contact->first_name} แล้ว",
            'email_id' => $email->id
        ];
    }
}
