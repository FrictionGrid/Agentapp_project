<?php
return [
    'api_key' => env('OPENAI_API_KEY'),
    
    // ถ้าไม่เขียนใน .env จะใช้ค่าเป็นค่าDefault ** กรณีที่ลบใน config **//
    'model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'timeout' => env('OPENAI_TIMEOUT', 30),
];
