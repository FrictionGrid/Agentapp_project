<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ChatMessage extends Model
{
    protected $table = 'chatmessages';
    
    protected $fillable = [
        'session_id',
        'role',
        'message'
    ];

    public $timestamps = true;

    /**
     * ดึงข้อความล่าสุดจาก session (เพิ่มเป็น 20 ข้อความเพื่อ context ที่ดีขึ้น)
     */
    public static function getRecentMessages(string $sessionId, int $limit = 20): array
    {
        $messages = self::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        \Log::info('[ChatMessage] Retrieved messages', [
            'session_id' => $sessionId,
            'found_count' => $messages->count(),
            'limit' => $limit
        ]);

        return $messages->map(function ($message) {
            return [
                'role' => $message->role,
                'content' => $message->message
            ];
        })->toArray();
    }

    /**
     * บันทึกข้อความใหม่
     */
    public static function saveMessage(string $sessionId, string $role, string $message): void
    {
        $savedMessage = self::create([
            'session_id' => $sessionId,
            'role' => $role,
            'message' => $message
        ]);
        
        \Log::info('[ChatMessage] Message saved', [
            'id' => $savedMessage->id,
            'session_id' => $sessionId,
            'role' => $role,
            'message_length' => strlen($message)
        ]);
    }

    /**
     * ลบข้อความเก่าที่เก็บไว้นานเกินกำหนด (เพื่อประสิทธิภาพและพื้นที่)
     */
    public static function cleanupOldMessages(int $daysOld = 30): int
    {
        $deletedCount = self::where('created_at', '<', now()->subDays($daysOld))->delete();
        
        \Log::info('[ChatMessage] Cleaned up old messages', [
            'deleted_count' => $deletedCount,
            'older_than_days' => $daysOld
        ]);
        
        return $deletedCount;
    }

    /**
     * ลบข้อความทั้งหมดของ session เฉพาะ
     */
    public static function clearSessionMessages(string $sessionId): int
    {
        $deletedCount = self::where('session_id', $sessionId)->delete();
        
        \Log::info('[ChatMessage] Cleared session messages', [
            'session_id' => $sessionId,
            'deleted_count' => $deletedCount
        ]);
        
        return $deletedCount;
    }

    /**
     * นับจำนวนข้อความในแต่ละ session
     */
    public static function getMessageCount(string $sessionId): int
    {
        return self::where('session_id', $sessionId)->count();
    }
}