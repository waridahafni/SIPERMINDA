<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_kendala', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_data_id')->constrained('permintaan_data')->cascadeOnDelete();
            $table->text('laporan');
            $table->text('tanggapan')->nullable();
            $table->foreignId('ditangani_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diselesaikan_at')->nullable();
            $table->timestamps();
            $table->index(['diselesaikan_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_kendala');
    }
};
