<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chatmessages', function (Blueprint $table) {
            $table->id();
             $table->string('session_id')->index(); // เก็บ session ของผู้ใช้
            $table->enum('role', ['user', 'assistant']); // บทบาทของข้อความ
            $table->text('message'); // เนื้อหาข้อความ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatmessages');
    }
};
