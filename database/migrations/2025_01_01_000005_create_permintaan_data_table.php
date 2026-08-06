<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_data', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_tiket', 30)->unique();
            $table->foreignId('pemohon_id')->constrained('pemohon')->cascadeOnDelete();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_data')->nullOnDelete();
            $table->string('jenis_data');
            $table->text('tujuan_penggunaan');
            $table->string('periode_data', 50)->nullable();
            $table->string('status', 30)->default('diajukan');
            $table->text('catatan_penolakan')->nullable();
            $table->string('file_hasil_path')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_data');
    }
};
