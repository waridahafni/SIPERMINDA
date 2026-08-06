<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_data_id')->nullable()->constrained('permintaan_data')->nullOnDelete();
            $table->string('tujuan_email');
            $table->string('jenis_notifikasi', 100);
            $table->string('status_kirim', 10);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_log');
    }
};
