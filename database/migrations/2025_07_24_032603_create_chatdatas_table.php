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
        Schema::create('chatdatas', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->index();
            $table->string('state'); // เก็บสถานะว่ากำลังทำอะไรอยู่ //
            $table->json('data')->nullable(); // เก็บข้อมูลสนทนา //
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatdatas');
    }
};
