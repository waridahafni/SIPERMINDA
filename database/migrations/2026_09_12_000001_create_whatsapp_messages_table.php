<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->text('phone'); // Enkripsi aplikasi; nomor tidak disimpan sebagai plaintext.
            $table->string('type', 40)->default('otp');
            $table->string('message_id')->nullable()->unique();
            $table->string('status', 24)->default('pending');
            $table->string('error', 64)->nullable(); // Hanya kode aman, bukan body respons Meta.
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('status_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
