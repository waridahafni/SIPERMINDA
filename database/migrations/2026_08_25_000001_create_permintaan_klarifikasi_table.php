<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_klarifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_data_id')->constrained('permintaan_data')->cascadeOnDelete();
            $table->string('tahap', 10);
            $table->foreignId('diminta_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('pertanyaan');
            $table->text('catatan_internal')->nullable();
            $table->text('jawaban')->nullable();
            $table->timestamp('dijawab_at')->nullable();
            $table->timestamps();

            $table->index(
                ['permintaan_data_id', 'dijawab_at'],
                'permintaan_klarifikasi_aktif_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_klarifikasi');
    }
};
