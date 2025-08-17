<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Casts\Attribute; //ฟังชั่นเสริมไปดูรายละเอียดเพิ่มเติม//
use App\Models\emaildata;     
use App\Models\customer;

class pivot_email_customer extends pivot
{
    protected $table = 'pivot_email_contacts'; 
    protected $fillable = [
        'email_id',
        'contact_id',
        'status',
        'lecture'
    ];
    public function email()
    {
        return $this->belongsTo(emaildata::class, 'email_id');
    }
    public function contact()
    {
        return $this->belongsTo(customer::class, 'contact_id');
    }

   // ดึงมาโชว์ตัวใหญ่ เก็บตัวเล็ก //
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn($v)=>ucfirst($v),
            set: fn($v)=>strtolower($v),
        );
    }
}
