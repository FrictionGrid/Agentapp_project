<?php
return [
    'api_key' => env('OPENAI_API_KEY'),
    
    // ถ้าไม่เขียนใน .env จะใช้ค่าเป็นค่าDefault ** กรณีที่ลบใน config **//
    'model'   => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
    'timeout' => env('OPENAI_TIMEOUT', 30),
];
